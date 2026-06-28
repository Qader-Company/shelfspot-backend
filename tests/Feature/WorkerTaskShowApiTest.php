<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
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

class WorkerTaskShowApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckApiKey::class,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_worker_can_show_own_single_task_with_submissions(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker, $taskService, $submission] = $this->assignedTaskWithSubmission();
        Sanctum::actingAs($worker->user, [PortalTypeEnum::WORKER->value, 'access']);

        $this->getJson($this->workerTaskUrl($task))
            ->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.assigned_worker_id', $worker->id)
            ->assertJsonPath('data.services.0.id', $taskService->id)
            ->assertJsonPath('data.services.0.submission.id', $submission->id)
            ->assertJsonPath('data.services.0.submission.worker_id', $worker->id)
            ->assertJsonPath('data.services.0.submission.form_data.items.0.availability', 'available');
    }

    public function test_worker_cannot_show_another_workers_single_task(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task] = $this->assignedTaskWithSubmission();
        $otherWorker = $this->worker();
        Sanctum::actingAs($otherWorker->user, [PortalTypeEnum::WORKER->value, 'access']);

        $this->getJson($this->workerTaskUrl($task))
            ->assertNotFound();
    }

    private function assignedTaskWithSubmission(): array
    {
        $company = Company::query()->create([
            'name' => 'Acme',
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('010########'),
            'cr_number' => fake()->unique()->numerify('CR-####'),
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);
        $worker = $this->worker();

        $task = Task::query()->create([
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
        ]);

        $service = Service::query()->create([
            'key' => ServiceTypeEnum::ON_SHELF_AVAILABILITY,
            'minimum_price' => 25,
            'minimum_execution_time' => 15,
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'company_id' => $company->id,
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

        $submission = TaskServiceSubmission::query()->create([
            'task_service_id' => $taskService->id,
            'worker_id' => $worker->id,
            'form_data' => ['items' => [['product_id' => $product->id, 'availability' => 'available']]],
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return [$task, $worker, $taskService, $submission];
    }

    private function worker(): Worker
    {
        return Worker::query()->create([
            'user_id' => User::factory()->create(['type' => PortalTypeEnum::WORKER])->id,
            'phone' => fake()->unique()->numerify('011########'),
            'is_active' => true,
        ]);
    }

    private function workerTaskUrl(Task $task): string
    {
        return '/api/v1/worker/tasks/'.$task->id;
    }
}
