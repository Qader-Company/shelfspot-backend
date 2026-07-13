<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\CanReopenTaskRule;
use App\Modules\V1\Tasks\Application\Services\TaskWorkerAssignmentManager;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentTypeEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;

class AdminReopenTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskWorkerAssignmentManager $assignmentManager,
    ) {}

    public function execute(Task $task, Worker $worker, ?User $admin = null, ?string $reason = null): Task
    {
        return DB::transaction(function () use ($task, $worker, $admin, $reason) {
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id, ['services']);

            Worker::query()->whereKey($worker->id)->lockForUpdate()->firstOrFail();

            $workerHasActiveTask = $this->taskRepository->query()
                ->where('assigned_worker_id', $worker->id)
                ->whereIn('status', TaskStatusEnum::values(TaskStatusEnum::workerActiveStatuses()))
                ->exists();

            CanReopenTaskRule::validate($lockedTask, $worker, $workerHasActiveTask);

            $fromStatus = $lockedTask->status;
            $previousWorkerId = $lockedTask->assigned_worker_id;
            $now = now();
            $assignmentType = $previousWorkerId === $worker->id
                ? TaskWorkerAssignmentTypeEnum::REOPENED_SAME_WORKER
                : TaskWorkerAssignmentTypeEnum::REOPENED_REASSIGNED;

            $lockedTask->forceFill([
                'status' => TaskStatusEnum::REOPENED,
                'assigned_worker_id' => $worker->id,
                'reopened_at' => $now,
                'reopen_deadline_at' => $now->copy()->startOfDay()->addDays(2),
                'reopen_reason' => $reason,
                'failure_reason' => null,
                'auto_accept_at' => null,
                'completed_at' => null,
                'started_at' => null,
                'expected_completion_at' => null,
                'in_progress_overdue_at' => null,
                'start_deadline_at' => null,
                'start_deadline_extension_minutes' => null,
                'start_deadline_extended_at' => null,
            ])->save();

            $lockedTask->services()->update(['status' => TaskServiceStatusEnum::PENDING->value]);
            $this->assignmentManager->assign($lockedTask, $worker, $assignmentType, $admin);

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $fromStatus,
                TaskStatusEnum::REOPENED,
                $admin,
                [
                    'actor_type' => 'admin',
                    'reason' => $reason,
                    'previous_worker_id' => $previousWorkerId,
                    'assigned_worker_id' => $worker->id,
                    'assignment_type' => $assignmentType->value,
                    'reopen_deadline_at' => $lockedTask->reopen_deadline_at?->toDateTimeString(),
                ]
            );

            return $lockedTask->refresh();
        });
    }
}
