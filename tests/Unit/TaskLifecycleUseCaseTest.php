<?php

namespace Tests\Unit;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use App\Modules\V1\Tasks\Application\UseCases\SubmitTaskServiceUseCase;
use App\Modules\V1\Tasks\Domain\Models\TaskService;
use App\Modules\V1\Tasks\Domain\Models\TaskServiceProduct;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Infrastructure\Persistence\Repositories\EloquentTaskRepository;
use App\Modules\V1\Tasks\Application\UseCases\AcceptTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\AdminReassignTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\CompleteTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\DeleteCompanyTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\StartTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\FailExpiredTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\WorkerCancelTaskUseCase;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskLifecycleUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_worker_accepts_pending_charged_task_on_execution_date(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();

        $acceptedTask = app(AcceptTaskUseCase::class)->execute($task, $worker);

        $this->assertSame(TaskStatusEnum::ACCEPTED, $acceptedTask->status);
        $this->assertSame($worker->id, $acceptedTask->assigned_worker_id);
        $this->assertTrue($acceptedTask->accepted_at->equalTo(now()));
        $this->assertTrue($acceptedTask->start_deadline_at->equalTo(now()->addMinutes(AcceptTaskUseCase::START_DEADLINE_MINUTES)));
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::PENDING->value,
            'to_status' => TaskStatusEnum::ACCEPTED->value,
            'changed_by' => $worker->user_id,
        ]);
    }

    public function test_worker_cannot_accept_task_outside_execution_date(): void
    {
        Carbon::setTestNow('2026-06-09 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker(['date' => '2026-06-10']);

        $this->expectException(ValidationException::class);

        app(AcceptTaskUseCase::class)->execute($task, $worker);
    }

    public function test_worker_starts_accepted_task_inside_geofence(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(AcceptTaskUseCase::class)->execute($task, $worker);

        $startedTask = app(StartTaskUseCase::class)->execute($acceptedTask, $worker, 30.0444, 31.2357);

        $this->assertSame(TaskStatusEnum::IN_PROGRESS, $startedTask->status);
        $this->assertTrue($startedTask->started_at->equalTo(now()));
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::ACCEPTED->value,
            'to_status' => TaskStatusEnum::IN_PROGRESS->value,
            'changed_by' => $worker->user_id,
        ]);
    }

    public function test_worker_cannot_start_after_deadline(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(AcceptTaskUseCase::class)->execute($task, $worker);

        Carbon::setTestNow('2026-06-10 09:16:00');

        $this->expectException(ValidationException::class);

        app(StartTaskUseCase::class)->execute($acceptedTask, $worker, 30.0444, 31.2357);
    }

    public function test_worker_cannot_start_outside_geofence(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(AcceptTaskUseCase::class)->execute($task, $worker);

        $this->expectException(ValidationException::class);

        app(StartTaskUseCase::class)->execute($acceptedTask, $worker, 31.2001, 29.9187);
    }

    public function test_company_delete_hides_task_without_changing_operational_status(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(AcceptTaskUseCase::class)->execute($task, $worker);

        $deletedTask = app(DeleteCompanyTaskUseCase::class)->execute($acceptedTask, $worker->user);

        $this->assertSame(TaskStatusEnum::ACCEPTED, $deletedTask->status);
        $this->assertTrue($deletedTask->company_deleted_at->equalTo(now()));
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::ACCEPTED->value,
            'to_status' => TaskStatusEnum::COMPANY_DELETED->value,
            'changed_by' => $worker->user_id,
        ]);
    }

    public function test_repository_returns_tasks_assigned_to_worker_only(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker([
            'status' => TaskStatusEnum::ACCEPTED,
        ]);
        $task->forceFill(['assigned_worker_id' => $worker->id])->save();

        [$otherTask, $otherWorker] = $this->pendingTaskAndWorker([
            'status' => TaskStatusEnum::ACCEPTED,
        ]);
        $otherTask->forceFill(['assigned_worker_id' => $otherWorker->id])->save();

        $tasks = app(EloquentTaskRepository::class)->assignedToWorker($worker->id);

        $this->assertSame([$task->id], $tasks->pluck('id')->all());
    }

    public function test_worker_submits_task_service_after_task_started(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $startedTask = app(StartTaskUseCase::class)->execute(
            app(AcceptTaskUseCase::class)->execute($task, $worker),
            $worker,
            30.0444,
            31.2357
        );
        [$taskService, $product] = $this->taskServiceWithProduct($startedTask);

        $submission = app(SubmitTaskServiceUseCase::class)->execute(
            task: $startedTask,
            taskService: $taskService,
            worker: $worker,
            formData: [
                'items' => [[
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'availability' => 'available',
                ]],
                'additional_notes' => 'Shelf was fully stocked.',
            ]
        );

        $this->assertSame($worker->id, $submission->worker_id);
        $this->assertSame('completed', $submission->status);
        $this->assertTrue($submission->completed_at->equalTo(now()));
        $this->assertSame(TaskServiceStatusEnum::COMPLETED, $taskService->refresh()->status);
        $this->assertDatabaseHas('task_service_submissions', [
            'id' => $submission->id,
            'task_service_id' => $taskService->id,
            'worker_id' => $worker->id,
            'status' => 'completed',
        ]);
    }


    public function test_worker_completes_task_after_all_services_are_completed(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $startedTask = app(StartTaskUseCase::class)->execute(
            app(AcceptTaskUseCase::class)->execute($task, $worker),
            $worker,
            30.0444,
            31.2357
        );
        [$taskService] = $this->taskServiceWithProduct($startedTask);
        $taskService->forceFill(['status' => TaskServiceStatusEnum::COMPLETED])->save();

        $completedTask = app(CompleteTaskUseCase::class)->execute($startedTask, $worker);

        $this->assertSame(TaskStatusEnum::COMPLETED, $completedTask->status);
        $this->assertTrue($completedTask->completed_at->equalTo(now()));
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::IN_PROGRESS->value,
            'to_status' => TaskStatusEnum::COMPLETED->value,
            'changed_by' => $worker->user_id,
        ]);
    }

    public function test_worker_cannot_complete_task_before_services_are_completed(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $startedTask = app(StartTaskUseCase::class)->execute(
            app(AcceptTaskUseCase::class)->execute($task, $worker),
            $worker,
            30.0444,
            31.2357
        );
        $this->taskServiceWithProduct($startedTask);

        $this->expectException(ValidationException::class);

        app(CompleteTaskUseCase::class)->execute($startedTask, $worker);
    }

    public function test_worker_cancel_records_internal_reason_and_history(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(AcceptTaskUseCase::class)->execute($task, $worker);

        $cancelledTask = app(WorkerCancelTaskUseCase::class)->execute($acceptedTask, $worker, 'Vehicle issue');

        $this->assertSame(TaskStatusEnum::WORKER_CANCELLED, $cancelledTask->status);
        $this->assertSame('Vehicle issue', $cancelledTask->worker_cancel_reason);
        $this->assertTrue($cancelledTask->worker_cancelled_at->equalTo(now()));
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::ACCEPTED->value,
            'to_status' => TaskStatusEnum::WORKER_CANCELLED->value,
            'changed_by' => $worker->user_id,
        ]);
    }

    public function test_admin_reassigns_cancelled_task_to_available_worker(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $cancelledTask = app(WorkerCancelTaskUseCase::class)->execute(
            app(AcceptTaskUseCase::class)->execute($task, $worker),
            $worker,
            'Emergency'
        );
        $newWorker = Worker::query()->create([
            'user_id' => User::factory()->create(['type' => PortalTypeEnum::WORKER])->id,
            'phone' => fake()->unique()->numerify('012########'),
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);

        $reassignedTask = app(AdminReassignTaskUseCase::class)->execute($cancelledTask, $newWorker, $admin);

        $this->assertSame(TaskStatusEnum::ACCEPTED, $reassignedTask->status);
        $this->assertSame($newWorker->id, $reassignedTask->assigned_worker_id);
        $this->assertNull($reassignedTask->worker_cancel_reason);
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::WORKER_CANCELLED->value,
            'to_status' => TaskStatusEnum::ACCEPTED->value,
            'changed_by' => $admin->id,
        ]);
    }

    public function test_admin_cannot_reassign_to_worker_with_in_progress_task(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $cancelledTask = app(WorkerCancelTaskUseCase::class)->execute(
            app(AcceptTaskUseCase::class)->execute($task, $worker),
            $worker,
            'Emergency'
        );
        [$busyTask, $busyWorker] = $this->pendingTaskAndWorker(['status' => TaskStatusEnum::IN_PROGRESS]);
        $busyTask->forceFill(['assigned_worker_id' => $busyWorker->id])->save();

        $this->expectException(ValidationException::class);

        app(AdminReassignTaskUseCase::class)->execute($cancelledTask, $busyWorker, null);
    }

    public function test_expired_pending_and_accepted_tasks_are_failed(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$pendingTask] = $this->pendingTaskAndWorker(['date' => '2026-06-09']);
        [$acceptedTask, $worker] = $this->pendingTaskAndWorker(['date' => '2026-06-10']);
        $acceptedTask->forceFill([
            'status' => TaskStatusEnum::ACCEPTED,
            'assigned_worker_id' => $worker->id,
            'accepted_at' => now()->subMinutes(20),
            'start_deadline_at' => now()->subMinutes(5),
        ])->save();

        $failed = app(FailExpiredTaskUseCase::class)->execute();

        $this->assertSame(2, $failed);
        $this->assertSame(TaskStatusEnum::FAILED, $pendingTask->refresh()->status);
        $this->assertSame(TaskStatusEnum::FAILED, $acceptedTask->refresh()->status);
    }

    public function test_worker_cannot_submit_task_service_before_task_started(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(AcceptTaskUseCase::class)->execute($task, $worker);
        [$taskService, $product] = $this->taskServiceWithProduct($acceptedTask);

        $this->expectException(ValidationException::class);

        app(SubmitTaskServiceUseCase::class)->execute(
            task: $acceptedTask,
            taskService: $taskService,
            worker: $worker,
            formData: [
                'items' => [[
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'availability' => 'available',
                ]],
            ]
        );
    }

    private function pendingTaskAndWorker(array $taskOverrides = []): array
    {
        $company = Company::query()->create([
            'name' => 'Acme',
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('010########'),
            'cr_number' => fake()->unique()->numerify('CR-####'),
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['type' => PortalTypeEnum::WORKER]);
        $worker = Worker::query()->create([
            'user_id' => $user->id,
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
            'subtotal' => 100,
            'total_price' => 100,
            'status' => TaskStatusEnum::PENDING,
            'payment_status' => TaskPaymentStatusEnum::CHARGED,
            'charged_at' => now(),
        ], $taskOverrides));

        return [$task, $worker];
    }

    private function taskServiceWithProduct(Task $task): array
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
            'status' => TaskServiceStatusEnum::PENDING,
            'sort_order' => 1,
        ]);

        TaskServiceProduct::query()->create([
            'task_service_id' => $taskService->id,
            'product_id' => $product->id,
            'product_details' => [],
        ]);

        return [$taskService, $product];
    }

}
