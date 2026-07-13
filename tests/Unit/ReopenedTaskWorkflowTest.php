<?php

namespace Tests\Unit;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\Tasks\Application\Services\TaskWorkerAssignmentManager;
use App\Modules\V1\Tasks\Application\UseCases\AdminReopenTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\CompanyRejectTaskUseCase;
use App\Modules\V1\Tasks\Application\UseCases\FailExpiredReopenedTasksUseCase;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskFailureReasonEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentOutcomeEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentTypeEnum;
use App\Modules\V1\Tasks\Infrastructure\Persistence\Repositories\EloquentTaskRepository;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\Workers\Presentation\Http\Resources\WorkerPriorityTaskResource;
use App\Modules\V1\Workers\Presentation\Http\Resources\WorkerResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReopenedTaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_reopen_reassigns_task_and_preserves_the_original_worker_assignment(): void
    {
        Carbon::setTestNow('2026-07-12 14:00:00');
        $company = $this->company();
        $originalWorker = $this->worker();
        $replacementWorker = $this->worker();
        $companyUser = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);
        $admin = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);
        $task = $this->task($company, $originalWorker, ['status' => TaskStatusEnum::COMPLETED]);

        app(TaskWorkerAssignmentManager::class)->assign(
            $task,
            $originalWorker,
            TaskWorkerAssignmentTypeEnum::INITIAL,
            $originalWorker->user,
        );

        $rejectedTask = app(CompanyRejectTaskUseCase::class)->execute(
            $task,
            $companyUser,
            'The requested shelf evidence is incomplete.'
        );

        $reopenedTask = app(AdminReopenTaskUseCase::class)->execute(
            $rejectedTask,
            $replacementWorker,
            $admin,
            'Please complete the missing evidence.'
        );

        $this->assertSame(TaskStatusEnum::REOPENED, $reopenedTask->status);
        $this->assertSame($replacementWorker->id, $reopenedTask->assigned_worker_id);
        $this->assertTrue($reopenedTask->reopen_deadline_at->equalTo(now()->startOfDay()->addDays(2)));
        $this->assertDatabaseHas('task_worker_assignments', [
            'task_id' => $task->id,
            'worker_id' => $originalWorker->id,
            'assignment_type' => TaskWorkerAssignmentTypeEnum::INITIAL->value,
            'outcome' => TaskWorkerAssignmentOutcomeEnum::REJECTED->value,
        ]);
        $this->assertDatabaseHas('task_worker_assignments', [
            'task_id' => $task->id,
            'worker_id' => $replacementWorker->id,
            'assignment_type' => TaskWorkerAssignmentTypeEnum::REOPENED_REASSIGNED->value,
            'outcome' => null,
        ]);
    }

    public function test_expired_reopened_task_fails_without_refunding_and_unassigns_the_worker(): void
    {
        Carbon::setTestNow('2026-07-14 00:00:00');
        $company = $this->company();
        $worker = $this->worker();
        $task = $this->task($company, $worker, [
            'status' => TaskStatusEnum::REOPENED,
            'reopen_deadline_at' => now(),
        ]);

        app(TaskWorkerAssignmentManager::class)->assign(
            $task,
            $worker,
            TaskWorkerAssignmentTypeEnum::REOPENED_SAME_WORKER,
        );

        $failed = app(FailExpiredReopenedTasksUseCase::class)->execute();

        $task->refresh();
        $this->assertSame(1, $failed);
        $this->assertSame(TaskStatusEnum::FAILED, $task->status);
        $this->assertSame(TaskFailureReasonEnum::REOPEN_DEADLINE_EXPIRED, $task->failure_reason);
        $this->assertNull($task->assigned_worker_id);
        $this->assertSame(TaskPaymentStatusEnum::CHARGED, $task->payment_status);
        $this->assertDatabaseHas('task_worker_assignments', [
            'task_id' => $task->id,
            'worker_id' => $worker->id,
            'outcome' => TaskWorkerAssignmentOutcomeEnum::REOPEN_DEADLINE_EXPIRED->value,
        ]);
    }

    public function test_worker_resource_includes_only_its_priority_reopened_tasks(): void
    {
        $company = $this->company();
        $worker = $this->worker();
        $task = $this->task($company, $worker, [
            'status' => TaskStatusEnum::REOPENED,
            'reopen_deadline_at' => now()->addDay(),
        ]);

        app(TaskWorkerAssignmentManager::class)->assign(
            $task,
            $worker,
            TaskWorkerAssignmentTypeEnum::REOPENED_SAME_WORKER,
        );

        $request = Request::create('/api/v1/worker/account/profile');
        $request->setUserResolver(fn () => $worker->user);
        $worker->load(['user', 'priorityTasks.currentWorkerAssignment']);
        $this->assertTrue($worker->priorityTasks->first()->relationLoaded('currentWorkerAssignment'));
        $this->assertNotNull($worker->priorityTasks->first()->currentWorkerAssignment);
        $this->assertSame(
            TaskWorkerAssignmentTypeEnum::REOPENED_SAME_WORKER->value,
            (new WorkerPriorityTaskResource($worker->priorityTasks->first()))->resolve($request)['assignment_type']
        );

        $data = (new WorkerResource($worker))
            ->resolve($request);

        $this->assertSame($task->id, $data['priority_tasks'][0]['id']);
        $this->assertSame(TaskWorkerAssignmentTypeEnum::REOPENED_SAME_WORKER->value, $data['priority_tasks'][0]['assignment_type']);
        $this->assertSame($task->reopen_deadline_at->toDateTimeString(), $data['priority_tasks'][0]['reopen_deadline_at']);
    }

    public function test_reopened_and_reassigned_work_is_prioritized_in_the_worker_task_list(): void
    {
        $company = $this->company();
        $worker = $this->worker();
        $historicalTask = $this->task($company, $worker, ['status' => TaskStatusEnum::ACCEPTED]);
        $priorityTask = $this->task($company, $worker, [
            'status' => TaskStatusEnum::REOPENED,
            'reopen_deadline_at' => now()->addDay(),
        ]);

        app(TaskWorkerAssignmentManager::class)->assign(
            $priorityTask,
            $worker,
            TaskWorkerAssignmentTypeEnum::REOPENED_SAME_WORKER,
        );

        $tasks = app(EloquentTaskRepository::class)->assignedToWorker(
            workerId: $worker->id,
            paginationType: 'paginate',
        );

        $this->assertSame([$priorityTask->id, $historicalTask->id], $tasks->pluck('id')->all());
    }

    private function company(): Company
    {
        return Company::query()->create([
            'name' => 'Acme',
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('010########'),
            'cr_number' => fake()->unique()->numerify('CR-####'),
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);
    }

    private function worker(): Worker
    {
        return Worker::query()->create([
            'user_id' => User::factory()->create(['type' => PortalTypeEnum::WORKER])->id,
            'phone' => fake()->unique()->numerify('011########'),
            'is_active' => true,
        ]);
    }

    private function task(Company $company, Worker $worker, array $overrides = []): Task
    {
        return Task::query()->create(array_merge([
            'company_id' => $company->id,
            'date' => now()->toDateString(),
            'execution_time' => '00:00:00',
            'estimated_duration_minutes' => 60,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'location_name' => 'Store branch',
            'total_price' => 100,
            'status' => TaskStatusEnum::PENDING,
            'payment_status' => TaskPaymentStatusEnum::CHARGED,
            'assigned_worker_id' => $worker->id,
            'charged_at' => now(),
        ], $overrides));
    }
}
