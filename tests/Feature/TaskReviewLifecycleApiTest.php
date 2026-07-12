<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Http\Middleware\CheckScopedPermission;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;
use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskService;
use App\Modules\V1\Tasks\Domain\Models\TaskServiceProduct;
use App\Modules\V1\Tasks\Domain\Models\TaskServiceSubmission;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskReviewLifecycleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckApiKey::class,
            CheckScopedPermission::class,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_company_task_show_exposes_review_fields_and_progress_contract(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $companyUser] = $this->completedTaskWithCompanyUser();
        Sanctum::actingAs($companyUser, [PortalTypeEnum::COMPANY->value, 'access']);

        $this->getJson($this->companyTaskUrl($task), $this->companyHeaders($task->company))
            ->assertOk()
            ->assertJsonPath('data.status', TaskStatusEnum::COMPLETED->value)
            ->assertJsonPath('data.rejection_reason', null)
            ->assertJsonPath('data.progress.total_services', 1)
            ->assertJsonPath('data.progress.completed_services', 1)
            ->assertJsonPath('data.progress.remaining_services', 0)
            ->assertJsonPath('data.progress.percentage', 100)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'completed_at',
                    'rejected_at',
                    'rejection_reason',
                    'company_accepted_at',
                    'auto_accept_at',
                    'reopened_at',
                    'reopen_reason',
                    'progress' => [
                        'total_services',
                        'completed_services',
                        'remaining_services',
                        'percentage',
                    ],
                    'services',
                ],
            ]);
    }

    public function test_company_can_accept_completed_task_via_api(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $companyUser] = $this->completedTaskWithCompanyUser();
        Sanctum::actingAs($companyUser, [PortalTypeEnum::COMPANY->value, 'access']);

        $feedback = [
            'platform_comment' => 'The platform made the task easy to follow.',
            'worker_comment' => 'The worker was punctual and professional.',
            'overall_comment' => 'A very good experience overall.',
        ];

        $this->patchJson($this->companyTaskUrl($task, 'accept'), ['feedback' => $feedback], $this->companyHeaders($task->company))
            ->assertOk()
            ->assertJsonPath('data.status', TaskStatusEnum::ACCEPTED->value)
            ->assertJsonPath('data.company_accepted_at', now()->toDateTimeString())
            ->assertJsonPath('data.feedback', $feedback);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatusEnum::ACCEPTED->value,
            'feedback' => json_encode($feedback),
        ]);
    }

    public function test_company_accept_feedback_rejects_unknown_fields(): void
    {
        [$task, $companyUser] = $this->completedTaskWithCompanyUser();
        Sanctum::actingAs($companyUser, [PortalTypeEnum::COMPANY->value, 'access']);

        $this->patchJson(
            $this->companyTaskUrl($task, 'accept'),
            ['feedback' => ['worker_comment' => 'Great work.', 'rating' => 5]],
            $this->companyHeaders($task->company)
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['feedback']);
    }

    public function test_company_reject_requires_reason_and_then_exposes_admin_message_and_reopen_flow(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $companyUser] = $this->completedTaskWithCompanyUser();
        $admin = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);

        Sanctum::actingAs($companyUser, [PortalTypeEnum::COMPANY->value, 'access']);

        $this->postJson($this->companyTaskUrl($task, 'reject'), [], $this->companyHeaders($task->company))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);

        $this->postJson(
            $this->companyTaskUrl($task, 'reject'),
            ['reason' => 'Submitted photos are not clear enough.'],
            $this->companyHeaders($task->company)
        )
            ->assertOk()
            ->assertJsonPath('data.status', TaskStatusEnum::REJECTED->value)
            ->assertJsonPath('data.rejection_reason', 'Submitted photos are not clear enough.');

        $this->postJson(
            $this->companyTaskUrl($task, 'review-messages'),
            ['message' => 'Please review the rejection details.'],
            $this->companyHeaders($task->company)
        )
            ->assertCreated()
            ->assertJsonPath('data.sender_role', 'company')
            ->assertJsonPath('data.message', 'Please review the rejection details.');

        Sanctum::actingAs($admin, [PortalTypeEnum::ADMIN->value, 'access']);

        $this->postJson($this->adminTaskUrl($task, 'review-messages'), ['message' => 'We will reopen this task.'])
            ->assertCreated()
            ->assertJsonPath('data.sender_role', 'admin')
            ->assertJsonPath('data.message', 'We will reopen this task.');

        $this->getJson($this->adminTaskUrl($task, 'review-messages'))
            ->assertOk()
            ->assertJsonCount(2, 'data.data');

        $this->postJson($this->adminTaskUrl($task, 'reopen'), ['reason' => 'Company rejection is valid.'])
            ->assertOk()
            ->assertJsonPath('data.status', TaskStatusEnum::REOPENED->value)
            ->assertJsonPath('data.reopen_reason', 'Company rejection is valid.');

        $this->assertDatabaseHas('task_services', [
            'task_id' => $task->id,
            'status' => TaskServiceStatusEnum::PENDING->value,
        ]);
    }

    public function test_company_cannot_reject_after_auto_accept_deadline_contract(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $companyUser] = $this->completedTaskWithCompanyUser([
            'auto_accept_at' => now()->subSecond(),
        ]);
        Sanctum::actingAs($companyUser, [PortalTypeEnum::COMPANY->value, 'access']);

        $this->postJson(
            $this->companyTaskUrl($task, 'reject'),
            ['reason' => 'This rejection is after the review window.'],
            $this->companyHeaders($task->company)
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['task']);
    }

    private function completedTaskWithCompanyUser(array $taskOverrides = []): array
    {
        $company = Company::query()->create([
            'name' => 'Acme',
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('010########'),
            'cr_number' => fake()->unique()->numerify('CR-####'),
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);
        $companyUser = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);
        CompanyUser::query()->create([
            'company_id' => $company->id,
            'user_id' => $companyUser->id,
            'is_owner' => true,
            'is_active' => true,
        ]);

        $workerUser = User::factory()->create(['type' => PortalTypeEnum::WORKER]);
        $worker = Worker::query()->create([
            'user_id' => $workerUser->id,
            'phone' => fake()->unique()->numerify('011########'),
            'is_active' => true,
        ]);

        $task = Task::query()->create(array_merge([
            'company_id' => $company->id,
            'date' => '2026-06-10',
            'execution_time' => '00:00:00',
            'estimated_duration_minutes' => 60,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'total_price' => 100,
            'status' => TaskStatusEnum::COMPLETED,
            'payment_status' => TaskPaymentStatusEnum::CHARGED,
            'assigned_worker_id' => $worker->id,
            'charged_at' => now(),
            'accepted_at' => now()->subHour(),
            'started_at' => now()->subMinutes(45),
            'completed_at' => now(),
            'auto_accept_at' => now()->addDays(2),
        ], $taskOverrides));

        [$taskService] = $this->taskServiceWithSubmission($task, $worker);

        return [$task->load(['services.submission', 'company']), $companyUser, $taskService];
    }

    private function taskServiceWithSubmission(Task $task, Worker $worker): array
    {
        $service = Service::query()->create([
            'key' => ServiceTypeEnum::ON_SHELF_AVAILABILITY,
            'minimum_price' => 25,
            'minimum_execution_time' => 15,
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'company_id' => $task->company_id,
            'name' => 'Product '.fake()->unique()->word(),
            'description' => 'Test product',
            'sku' => fake()->unique()->bothify('SKU-####'),
            'is_active' => true,
        ]);

        $taskService = TaskService::query()->create([
            'task_id' => $task->id,
            'service_id' => $service->id,
            'execution_instructions' => 'Check shelf availability.',
            'request_details' => [],
            'unit_price' => 25,
            'status' => TaskServiceStatusEnum::COMPLETED,
            'sort_order' => 1,
        ]);

        TaskServiceProduct::query()->create([
            'task_service_id' => $taskService->id,
            'product_id' => $product->id,
            'product_details' => [],
        ]);

        TaskServiceSubmission::query()->create([
            'task_service_id' => $taskService->id,
            'worker_id' => $worker->id,
            'form_data' => ['items' => [['product_id' => $product->id, 'availability' => 'available']]],
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return [$taskService, $product];
    }

    private function companyTaskUrl(Task $task, ?string $suffix = null): string
    {
        return '/api/v1/company/tasks/'.$task->id.($suffix ? '/'.$suffix : '');
    }

    private function adminTaskUrl(Task $task, ?string $suffix = null): string
    {
        return '/api/v1/admin/tasks/'.$task->id.($suffix ? '/'.$suffix : '');
    }

    private function companyHeaders(Company $company): array
    {
        return ['X-Company-id' => $company->id];
    }
}
