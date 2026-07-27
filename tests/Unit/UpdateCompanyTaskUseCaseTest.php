<?php

namespace Tests\Unit;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\CompaniesWallets\Domain\Repositories\CompaniesWalletRepositoryInterface;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use App\Modules\V1\Tasks\Application\UseCases\UpdateCompanyTaskUseCase;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskService;
use App\Modules\V1\Tasks\Domain\Models\TaskServiceProduct;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskServiceResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateCompanyTaskUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconciles_services_products_and_attachments_without_recreating_the_retained_service(): void
    {
        [$task, $firstTaskService, $secondTaskService, $product, $media] = $this->draftTaskWithServices();
        $this->assertDatabaseHas('media', ['id' => $media->id, 'model_id' => $firstTaskService->id]);
        $this->assertSame(1, $firstTaskService->media()->count());

        $updatedTask = app(UpdateCompanyTaskUseCase::class)->execute($task, [
            'services' => [
                [
                    'task_service_id' => $firstTaskService->id,
                    'service_key' => ServiceTypeEnum::PRIMARY_DISPLAY->value,
                    'service_id' => $firstTaskService->service_id,
                    'execution_instructions' => 'Updated instructions.',
                    'products' => [
                        ['product_id' => $product->id],
                    ],
                    'keep_attachment_ids' => [$media->id],
                ],
            ],
        ]);

        $this->assertSame($firstTaskService->id, $updatedTask->services->sole()->id);
        $this->assertSame('Updated instructions.', $updatedTask->services->sole()->execution_instructions);
        $this->assertSame($media->id, (new TaskServiceResource($updatedTask->services->sole()))->resolve()['attachments'][0]['id']);
        $this->assertDatabaseMissing('task_services', ['id' => $secondTaskService->id]);
        $this->assertDatabaseHas('task_service_products', [
            'task_service_id' => $firstTaskService->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('media', ['id' => $media->id, 'model_id' => $firstTaskService->id]);
    }

    public function test_updates_root_fields_without_touching_services_when_services_are_omitted(): void
    {
        [$task, $firstTaskService, $secondTaskService] = $this->draftTaskWithServices();

        app(UpdateCompanyTaskUseCase::class)->execute($task, ['notes' => 'Updated note.']);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'notes' => 'Updated note.']);
        $this->assertDatabaseHas('task_services', ['id' => $firstTaskService->id]);
        $this->assertDatabaseHas('task_services', ['id' => $secondTaskService->id]);
    }

    public function test_replaces_attachments_with_the_requested_final_set(): void
    {
        [$task, $firstTaskService, , $product, $media] = $this->draftTaskWithServices();

        app(UpdateCompanyTaskUseCase::class)->execute(
            $task,
            [
                'services' => [
                    [
                        'task_service_id' => $firstTaskService->id,
                        'service_key' => ServiceTypeEnum::PRIMARY_DISPLAY->value,
                        'service_id' => $firstTaskService->service_id,
                        'products' => [
                            ['product_id' => $product->id],
                        ],
                        'keep_attachment_ids' => [],
                    ],
                ],
            ],
            [
                'services' => [
                    [
                        'request_files' => [
                            'planogram_files' => [UploadedFile::fake()->create('replacement.pdf', 1, 'application/pdf')],
                        ],
                    ],
                ],
            ],
        );

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertSame(1, TaskService::query()->findOrFail($firstTaskService->id)->media()->count());
    }

    public function test_allows_pending_configuration_updates_and_refunds_a_price_decrease(): void
    {
        [$task, $firstTaskService, $secondTaskService, $product] = $this->draftTaskWithServices();
        $task->forceFill([
            'status' => TaskStatusEnum::PENDING,
            'payment_status' => TaskPaymentStatusEnum::CHARGED,
        ])->save();

        $updatedTask = app(UpdateCompanyTaskUseCase::class)->execute($task, [
            'services' => [[
                'task_service_id' => $firstTaskService->id,
                'service_key' => ServiceTypeEnum::PRIMARY_DISPLAY->value,
                'service_id' => $firstTaskService->service_id,
                'products' => [['product_id' => $product->id]],
            ]],
        ]);

        $this->assertSame('50.00', $updatedTask->total_price);
        $this->assertDatabaseMissing('task_services', ['id' => $secondTaskService->id]);
        $this->assertDatabaseHas('company_wallet_transactions', [
            'company_id' => $task->company_id,
            'type' => CompanyWalletTransactionTypeEnum::TASK_REFUND->value,
            'amount' => 25,
            'reference_id' => $task->id,
        ]);
    }

    public function test_pending_configuration_increase_charges_only_the_price_difference(): void
    {
        [$task, $firstTaskService, $secondTaskService, $product] = $this->draftTaskWithServices();
        $task->forceFill([
            'status' => TaskStatusEnum::PENDING,
            'payment_status' => TaskPaymentStatusEnum::CHARGED,
        ])->save();
        $firstTaskService->service->update(['price' => 100]);

        app(CompaniesWalletRepositoryInterface::class)->createTransaction([
            'company_id' => $task->company_id,
            'amount' => 50,
        ], CompanyWalletTransactionTypeEnum::ADMIN_GRANT);

        $updatedTask = app(UpdateCompanyTaskUseCase::class)->execute($task, [
            'services' => [
                [
                    'task_service_id' => $firstTaskService->id,
                    'service_key' => ServiceTypeEnum::PRIMARY_DISPLAY->value,
                    'service_id' => $firstTaskService->service_id,
                    'products' => [['product_id' => $product->id]],
                ],
                [
                    'task_service_id' => $secondTaskService->id,
                    'service_key' => ServiceTypeEnum::ON_SHELF_AVAILABILITY->value,
                    'service_id' => $secondTaskService->service_id,
                    'products' => [[
                        'product_id' => $product->id,
                        'product_details' => ['minimum_quantity' => 1],
                    ]],
                ],
            ],
        ]);

        $this->assertSame('125.00', $updatedTask->total_price);
        $this->assertDatabaseHas('company_wallet_transactions', [
            'company_id' => $task->company_id,
            'type' => CompanyWalletTransactionTypeEnum::TASK_PAYMENT->value,
            'amount' => 50,
            'reference_id' => $task->id,
        ]);
    }

    public function test_pending_price_increase_is_rejected_without_changing_the_task_when_wallet_funds_are_insufficient(): void
    {
        [$task, $firstTaskService, $secondTaskService, $product] = $this->draftTaskWithServices();
        $task->forceFill([
            'status' => TaskStatusEnum::PENDING,
            'payment_status' => TaskPaymentStatusEnum::CHARGED,
        ])->save();
        $firstTaskService->service->update(['price' => 100]);

        try {
            app(UpdateCompanyTaskUseCase::class)->execute($task, [
                'services' => [
                    [
                        'task_service_id' => $firstTaskService->id,
                        'service_key' => ServiceTypeEnum::PRIMARY_DISPLAY->value,
                        'service_id' => $firstTaskService->service_id,
                        'products' => [['product_id' => $product->id]],
                    ],
                    [
                        'task_service_id' => $secondTaskService->id,
                        'service_key' => ServiceTypeEnum::ON_SHELF_AVAILABILITY->value,
                        'service_id' => $secondTaskService->service_id,
                        'products' => [[
                            'product_id' => $product->id,
                            'product_details' => ['minimum_quantity' => 1],
                        ]],
                    ],
                ],
            ]);
            $this->fail('The update should require the additional wallet balance.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('wallet', $exception->errors());
        }

        $this->assertSame('75.00', $task->refresh()->total_price);
        $this->assertDatabaseCount('company_wallet_transactions', 0);
    }

    public function test_failed_task_can_be_rescheduled_with_only_a_date(): void
    {
        [$task] = $this->draftTaskWithServices();
        $task->forceFill([
            'status' => TaskStatusEnum::FAILED,
            'payment_status' => TaskPaymentStatusEnum::CHARGED,
        ])->save();

        $date = now()->addDay()->toDateString();
        $updatedTask = app(UpdateCompanyTaskUseCase::class)->execute($task, ['date' => $date]);

        $this->assertSame($date, $updatedTask->date->toDateString());
        $this->assertSame(TaskStatusEnum::PENDING, $updatedTask->status);
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::FAILED->value,
            'to_status' => TaskStatusEnum::PENDING->value,
        ]);
    }

    public function test_failed_task_rejects_any_update_other_than_a_date(): void
    {
        [$task] = $this->draftTaskWithServices();
        $task->forceFill(['status' => TaskStatusEnum::FAILED])->save();

        $this->expectException(ValidationException::class);

        app(UpdateCompanyTaskUseCase::class)->execute($task, ['notes' => 'Not allowed.']);
    }

    private function draftTaskWithServices(): array
    {
        $company = Company::query()->create([
            'name' => 'Acme',
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('010########'),
            'cr_number' => fake()->unique()->numerify('CR-####'),
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);
        app(TenantContextInterface::class)->setCompany((string) $company->id);

        $task = Task::query()->create([
            'company_id' => $company->id,
            'date' => now()->toDateString(),
            'execution_time' => '00:00:00',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'total_price' => 75,
            'status' => TaskStatusEnum::DRAFT,
            'payment_status' => TaskPaymentStatusEnum::FAILED,
        ]);

        $product = Product::query()->create([
            'company_id' => $company->id,
            'sku' => fake()->unique()->bothify('SKU-####'),
            'is_active' => true,
        ]);

        $primaryDisplay = Service::query()->create([
            'key' => ServiceTypeEnum::PRIMARY_DISPLAY,
            'price' => 50,
            'is_active' => true,
        ]);
        $onShelfAvailability = Service::query()->create([
            'key' => ServiceTypeEnum::ON_SHELF_AVAILABILITY,
            'price' => 25,
            'is_active' => true,
        ]);

        $firstTaskService = $this->taskService($task, $primaryDisplay, $product, 0);
        $secondTaskService = $this->taskService($task, $onShelfAvailability, $product, 1);
        $media = $firstTaskService
            ->addMedia(UploadedFile::fake()->create('planogram.pdf', 1, 'application/pdf'))
            ->withCustomProperties(['field' => 'planogram_files'])
            ->toMediaCollection('planogram_files');
        $secondTaskService
            ->addMedia(UploadedFile::fake()->create('second-planogram.pdf', 1, 'application/pdf'))
            ->withCustomProperties(['field' => 'planogram_files'])
            ->toMediaCollection('planogram_files');

        return [$task, $firstTaskService, $secondTaskService, $product, $media];
    }

    private function taskService(Task $task, Service $service, Product $product, int $sortOrder): TaskService
    {
        $taskService = TaskService::query()->create([
            'task_id' => $task->id,
            'service_id' => $service->id,
            'unit_price' => $service->price,
            'status' => TaskServiceStatusEnum::PENDING,
            'sort_order' => $sortOrder,
        ]);

        TaskServiceProduct::query()->create([
            'task_service_id' => $taskService->id,
            'product_id' => $product->id,
        ]);

        return $taskService;
    }
}
