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
use App\Modules\V1\Tasks\Application\UseCases\StartTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\AdminReassignTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\AdminReopenTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\AutoAcceptExpiredReviewTasksUseCase;
use App\Modules\V1\Tasks\Application\UseCases\CompanyAcceptTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\CompanyRejectTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\CompleteTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\DeleteCompanyTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\ForceDeleteCompanyDeletedTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\PurgeCompanyTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\RestoreCompanyTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\StartExecuteTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\FailExpiredTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\ExtendStartDeadlineUseCase;
use App\Modules\V1\Tasks\Application\UseCases\WorkerCancelTaskUseCase;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Application\Services\GeoDistanceCalculator;
use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\Workers\Infrastructure\Persistence\Repositories\EloquentWorkerRepository;
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

        $acceptedTask = app(StartTaskUseCase::class)->execute($task, $worker);

        $this->assertSame(TaskStatusEnum::STARTED, $acceptedTask->status);
        $this->assertSame($worker->id, $acceptedTask->assigned_worker_id);
        $this->assertTrue($acceptedTask->accepted_at->equalTo(now()));
        $this->assertTrue($acceptedTask->start_deadline_at->equalTo(now()->addMinutes(StartTaskUseCase::START_DEADLINE_MINUTES)));
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::PENDING->value,
            'to_status' => TaskStatusEnum::STARTED->value,
            'changed_by' => $worker->user_id,
        ]);
    }


    public function test_worker_extends_start_deadline_once_by_allowed_minutes(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(StartTaskUseCase::class)->execute($task, $worker);

        Carbon::setTestNow('2026-06-10 09:05:00');
        $extendedTask = app(ExtendStartDeadlineUseCase::class)->execute($acceptedTask, $worker, 10);

        $this->assertTrue($extendedTask->start_deadline_at->equalTo(Carbon::parse('2026-06-10 09:25:00')));
        $this->assertSame(10, $extendedTask->start_deadline_extension_minutes);
        $this->assertTrue($extendedTask->start_deadline_extended_at->equalTo(now()));

        $this->expectException(ValidationException::class);

        app(ExtendStartDeadlineUseCase::class)->execute($extendedTask, $worker, 5);
    }

    public function test_worker_cannot_extend_start_deadline_by_disallowed_minutes(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(StartTaskUseCase::class)->execute($task, $worker);

        $this->expectException(ValidationException::class);

        app(ExtendStartDeadlineUseCase::class)->execute($acceptedTask, $worker, 20);
    }

    public function test_worker_cannot_accept_task_outside_execution_date(): void
    {
        Carbon::setTestNow('2026-06-09 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker(['date' => '2026-06-10']);

        $this->expectException(ValidationException::class);

        app(StartTaskUseCase::class)->execute($task, $worker);
    }

    public function test_worker_starts_accepted_task_inside_geofence(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(StartTaskUseCase::class)->execute($task, $worker);

        $startedTask = app(StartExecuteTaskUseCase::class)->execute($acceptedTask, $worker, 30.0444, 31.2357);

        $this->assertSame(TaskStatusEnum::IN_PROGRESS, $startedTask->status);
        $this->assertTrue($startedTask->started_at->equalTo(now()));
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::STARTED->value,
            'to_status' => TaskStatusEnum::IN_PROGRESS->value,
            'changed_by' => $worker->user_id,
        ]);
    }

    public function test_worker_cannot_start_after_deadline(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(StartTaskUseCase::class)->execute($task, $worker);

        Carbon::setTestNow('2026-06-10 09:16:00');

        $this->expectException(ValidationException::class);

        app(StartExecuteTaskUseCase::class)->execute($acceptedTask, $worker, 30.0444, 31.2357);
    }

    public function test_worker_cannot_start_outside_geofence(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(StartTaskUseCase::class)->execute($task, $worker);

        $this->expectException(ValidationException::class);

        app(StartExecuteTaskUseCase::class)->execute($acceptedTask, $worker, 31.2001, 29.9187);
    }

    public function test_company_delete_hides_task_without_changing_operational_status(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(StartTaskUseCase::class)->execute($task, $worker);

        $deletedTask = app(DeleteCompanyTaskUseCase::class)->execute($acceptedTask, $worker->user);

        $this->assertSame(TaskStatusEnum::STARTED, $deletedTask->status);
        $this->assertTrue($deletedTask->company_deleted_at->equalTo(now()));
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::STARTED->value,
//            'to_status' => TaskStatusEnum::COMPANY_DELETED->value,
            'changed_by' => $worker->user_id,
        ]);
    }


    public function test_company_trash_restore_and_purge_are_company_scoped_visibility_changes(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $deletedTask = app(DeleteCompanyTaskUseCase::class)->execute($task, $worker->user);

        $repository = app(EloquentTaskRepository::class);

        $this->assertNull($repository->getById($task->id));
        $this->assertSame([$task->id], $repository->getCompanyTrash($task->company_id)->pluck('id')->all());

        $restoredTask = app(RestoreCompanyTaskUseCase::class)->execute($deletedTask);

        $this->assertNull($restoredTask->company_deleted_at);
        $this->assertNotNull($repository->getById($task->id));
        $this->assertSame([], $repository->getCompanyTrash($task->company_id)->pluck('id')->all());

        $deletedAgain = app(DeleteCompanyTaskUseCase::class)->execute($restoredTask, $worker->user);
        $purgedTask = app(PurgeCompanyTaskUseCase::class)->execute($deletedAgain);

        $this->assertTrue($purgedTask->company_purged_at->equalTo(now()));
        $this->assertNull($repository->getById($task->id));
        $this->assertSame([], $repository->getCompanyTrash($task->company_id)->pluck('id')->all());
        $this->assertSame([$task->id], $repository->getCompanyDeletedForAdmin()->pluck('id')->all());
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_admin_can_force_delete_company_deleted_task_only(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $deletedTask = app(DeleteCompanyTaskUseCase::class)->execute($task, $worker->user);

        $repository = app(EloquentTaskRepository::class);

        $this->assertSame([$task->id], $repository->getCompanyDeletedForAdmin()->pluck('id')->all());

        app(ForceDeleteCompanyDeletedTaskUseCase::class)->execute($deletedTask);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_company_cannot_delete_in_progress_task(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $startedTask = app(StartExecuteTaskUseCase::class)->execute(
            app(StartTaskUseCase::class)->execute($task, $worker),
            $worker,
            30.0444,
            31.2357
        );

        $this->expectException(ValidationException::class);

        app(DeleteCompanyTaskUseCase::class)->execute($startedTask, $worker->user);
    }

    public function test_repository_returns_tasks_assigned_to_worker_only(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker([
            'status' => TaskStatusEnum::STARTED,
        ]);
        $task->forceFill(['assigned_worker_id' => $worker->id])->save();

        [$otherTask, $otherWorker] = $this->pendingTaskAndWorker([
            'status' => TaskStatusEnum::STARTED,
        ]);
        $otherTask->forceFill(['assigned_worker_id' => $otherWorker->id])->save();

        $tasks = app(EloquentTaskRepository::class)->assignedToWorker($worker->id);

        $this->assertSame([$task->id], $tasks->pluck('id')->all());
    }

    public function test_worker_submits_task_service_after_task_started(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $startedTask = app(StartExecuteTaskUseCase::class)->execute(
            app(StartTaskUseCase::class)->execute($task, $worker),
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
        $startedTask = app(StartExecuteTaskUseCase::class)->execute(
            app(StartTaskUseCase::class)->execute($task, $worker),
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


    public function test_company_accepts_completed_task(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->completedTaskAndWorker();
        $companyUser = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);

        $acceptedTask = app(CompanyAcceptTaskUseCase::class)->execute($task, $companyUser);

        $this->assertSame(TaskStatusEnum::ACCEPTED, $acceptedTask->status);
        $this->assertTrue($acceptedTask->company_accepted_at->equalTo(now()));
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::COMPLETED->value,
            'to_status' => TaskStatusEnum::ACCEPTED->value,
            'changed_by' => $companyUser->id,
        ]);
    }

    public function test_company_rejects_completed_task_with_reason_before_review_window_expires(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->completedTaskAndWorker();
        $companyUser = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);

        $rejectedTask = app(CompanyRejectTaskUseCase::class)->execute($task, $companyUser, 'Photos do not match the requested shelf.');

        $this->assertSame(TaskStatusEnum::REJECTED, $rejectedTask->status);
        $this->assertSame('Photos do not match the requested shelf.', $rejectedTask->rejection_reason);
        $this->assertTrue($rejectedTask->rejected_at->equalTo(now()));
    }

    public function test_company_cannot_reject_completed_task_after_review_window_expires(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->completedTaskAndWorker();
        $companyUser = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);

        Carbon::setTestNow($task->auto_accept_at);

        $this->expectException(ValidationException::class);

        app(CompanyRejectTaskUseCase::class)->execute($task, $companyUser, 'Too late rejection reason.');
    }

    public function test_auto_accept_expired_review_tasks(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->completedTaskAndWorker();

        Carbon::setTestNow($task->auto_accept_at->copy()->addSecond());

        $accepted = app(AutoAcceptExpiredReviewTasksUseCase::class)->execute();

        $this->assertSame(1, $accepted);
        $this->assertSame(TaskStatusEnum::ACCEPTED, $task->refresh()->status);
        $this->assertTrue($task->company_accepted_at->equalTo(now()));
    }

    public function test_admin_reopens_rejected_task_and_worker_executes_again(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->completedTaskAndWorker();
        $companyUser = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);
        $admin = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);
        $rejectedTask = app(CompanyRejectTaskUseCase::class)->execute($task, $companyUser, 'Need clearer execution evidence.');

        $reopenedTask = app(AdminReopenTaskUseCase::class)->execute($rejectedTask, $admin, 'Company rejection is valid.');

        $this->assertSame(TaskStatusEnum::REOPENED, $reopenedTask->status);
        $this->assertNull($reopenedTask->auto_accept_at);
        $this->assertSame('Company rejection is valid.', $reopenedTask->reopen_reason);
        $this->assertSame(TaskServiceStatusEnum::PENDING, $reopenedTask->services()->first()->status);

        $executedTask = app(StartExecuteTaskUseCase::class)->execute($reopenedTask, $worker, 30.0444, 31.2357);

        $this->assertSame(TaskStatusEnum::IN_PROGRESS, $executedTask->status);
    }

    public function test_worker_cannot_complete_task_before_services_are_completed(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $startedTask = app(StartExecuteTaskUseCase::class)->execute(
            app(StartTaskUseCase::class)->execute($task, $worker),
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
        $acceptedTask = app(StartTaskUseCase::class)->execute($task, $worker);

        $cancelledTask = app(WorkerCancelTaskUseCase::class)->execute($acceptedTask, $worker, 'Vehicle issue');

        $this->assertSame(TaskStatusEnum::WORKER_CANCELLED, $cancelledTask->status);
        $this->assertSame('Vehicle issue', $cancelledTask->worker_cancel_reason);
        $this->assertTrue($cancelledTask->worker_cancelled_at->equalTo(now()));
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::STARTED->value,
            'to_status' => TaskStatusEnum::WORKER_CANCELLED->value,
            'changed_by' => $worker->user_id,
        ]);
    }

    public function test_admin_reassigns_cancelled_task_to_available_worker(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $cancelledTask = app(WorkerCancelTaskUseCase::class)->execute(
            app(StartTaskUseCase::class)->execute($task, $worker),
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

        $this->assertSame(TaskStatusEnum::STARTED, $reassignedTask->status);
        $this->assertSame($newWorker->id, $reassignedTask->assigned_worker_id);
        $this->assertNull($reassignedTask->worker_cancel_reason);
        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::WORKER_CANCELLED->value,
            'to_status' => TaskStatusEnum::STARTED->value,
            'changed_by' => $admin->id,
        ]);
    }

    public function test_admin_cannot_reassign_to_worker_with_in_progress_task(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $cancelledTask = app(WorkerCancelTaskUseCase::class)->execute(
            app(StartTaskUseCase::class)->execute($task, $worker),
            $worker,
            'Emergency'
        );
        [$busyTask, $busyWorker] = $this->pendingTaskAndWorker(['status' => TaskStatusEnum::IN_PROGRESS]);
        $busyTask->forceFill(['assigned_worker_id' => $busyWorker->id])->save();

        $this->expectException(ValidationException::class);

        app(AdminReassignTaskUseCase::class)->execute($cancelledTask, $busyWorker, null);
    }

    public function test_expired_pending_tasks_are_failed_and_expired_accepted_tasks_return_to_pending(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$pendingTask] = $this->pendingTaskAndWorker(['date' => '2026-06-09']);
        [$acceptedTask, $worker] = $this->pendingTaskAndWorker(['date' => '2026-06-10']);
        $acceptedTask->forceFill([
            'status' => TaskStatusEnum::STARTED,
            'assigned_worker_id' => $worker->id,
            'accepted_at' => now()->subMinutes(20),
            'start_deadline_at' => now()->subMinutes(5),
        ])->save();

        $failed = app(FailExpiredTaskUseCase::class)->execute();

        $this->assertSame(2, $failed);
        $this->assertSame(TaskStatusEnum::FAILED, $pendingTask->refresh()->status);
        $acceptedTask->refresh();
        $this->assertSame(TaskStatusEnum::PENDING, $acceptedTask->status);
        $this->assertNull($acceptedTask->assigned_worker_id);
        $this->assertNull($acceptedTask->accepted_at);
        $this->assertNull($acceptedTask->start_deadline_at);
    }

    public function test_worker_cannot_submit_task_service_before_task_started(): void
    {
        Carbon::setTestNow('2026-06-10 09:00:00');
        [$task, $worker] = $this->pendingTaskAndWorker();
        $acceptedTask = app(StartTaskUseCase::class)->execute($task, $worker);
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

    public function test_available_near_task_workers_are_active_not_busy_and_sorted_by_distance(): void
    {
        [$task, $nearestWorker] = $this->pendingTaskAndWorker();
        $nearestWorker->forceFill([
            'last_latitude' => 30.0450,
            'last_longitude' => 31.2360,
        ])->save();

        $fartherWorker = Worker::query()->create([
            'user_id' => User::factory()->create(['type' => PortalTypeEnum::WORKER])->id,
            'phone' => fake()->unique()->numerify('012########'),
            'is_active' => true,
            'last_latitude' => 30.0600,
            'last_longitude' => 31.2500,
        ]);

        $busyWorker = Worker::query()->create([
            'user_id' => User::factory()->create(['type' => PortalTypeEnum::WORKER])->id,
            'phone' => fake()->unique()->numerify('012########'),
            'is_active' => true,
            'last_latitude' => 30.0450,
            'last_longitude' => 31.2360,
        ]);
        Task::query()->create($task->only([
            'company_id',
            'date',
            'execution_time',
            'estimated_duration_minutes',
            'latitude',
            'longitude',
            'total_price',
            'payment_status',
            'charged_at',
        ]) + [
            'status' => TaskStatusEnum::IN_PROGRESS,
            'assigned_worker_id' => $busyWorker->id,
        ]);

        Worker::query()->create([
            'user_id' => User::factory()->create(['type' => PortalTypeEnum::WORKER])->id,
            'phone' => fake()->unique()->numerify('012########'),
            'is_active' => false,
            'last_latitude' => 30.0450,
            'last_longitude' => 31.2360,
        ]);

        $radius = 5.0;
        $workers = app(EloquentWorkerRepository::class)->availableNearTask(
            latitude: (float) $task->latitude,
            longitude: (float) $task->longitude,
            radiusKilometers: $radius,
            boundingBox: app(GeoDistanceCalculator::class)->boundingBox((float) $task->latitude, (float) $task->longitude, $radius)
        );

        $this->assertSame([$nearestWorker->id, $fartherWorker->id], $workers->pluck('id')->all());
        $this->assertLessThan($workers[1]->distance_km, $workers[0]->distance_km);
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


    private function completedTaskAndWorker(): array
    {
        [$task, $worker] = $this->pendingTaskAndWorker();
        $startedTask = app(StartExecuteTaskUseCase::class)->execute(
            app(StartTaskUseCase::class)->execute($task, $worker),
            $worker,
            30.0444,
            31.2357
        );
        [$taskService] = $this->taskServiceWithProduct($startedTask);
        $taskService->forceFill(['status' => TaskServiceStatusEnum::COMPLETED])->save();

        $completedTask = app(CompleteTaskUseCase::class)->execute($startedTask, $worker);

        return [$completedTask, $worker];
    }

}
