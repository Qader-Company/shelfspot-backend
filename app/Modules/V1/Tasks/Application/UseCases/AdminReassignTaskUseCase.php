<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\CanReassignTaskRule;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;

class AdminReassignTaskUseCase
{
    public function __construct(private readonly TaskRepositoryInterface $taskRepository)
    {
    }

    public function execute(Task $task, Worker $worker, ?User $admin = null): Task
    {
        return DB::transaction(function () use ($task, $worker, $admin) {
            /** @var Task $lockedTask */
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

            $fromStatus = $lockedTask->status;

            Worker::query()->whereKey($worker->id)->lockForUpdate()->firstOrFail();

            $workerHasActiveTask = $this->taskRepository->query()
                ->where('assigned_worker_id', $worker->id)
                ->whereIn('status', TaskStatusEnum::values(TaskStatusEnum::workerActiveStatuses()))
                ->exists();

            CanReassignTaskRule::validate($lockedTask, $worker, $workerHasActiveTask);

            $now = now();
            $lockedTask->forceFill([
                'status' => TaskStatusEnum::STARTED,
                'assigned_worker_id' => $worker->id,
                'accepted_at' => $now,
                'start_deadline_at' => $now->copy()->addMinutes(StartTaskUseCase::START_DEADLINE_MINUTES),
                'worker_cancelled_at' => null,
                'worker_cancel_reason' => null,
            ])->save();

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $fromStatus,
                TaskStatusEnum::STARTED,
                $admin ?? $worker,
                ['reassigned_worker_id' => $worker->id]
            );

            return $lockedTask->refresh();
        });
    }
}
