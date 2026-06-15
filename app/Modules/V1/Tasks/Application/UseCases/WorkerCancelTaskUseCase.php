<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkerCancelTaskUseCase
{
    public function __construct(private readonly TaskRepositoryInterface $taskRepository)
    {
    }

    public function execute(Task $task, Worker $worker, string $reason): Task
    {
        return DB::transaction(function () use ($task, $worker, $reason) {
            /** @var Task $lockedTask */
            $lockedTask = $this->taskRepository
                ->query()
                ->whereKey($task->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = $lockedTask->status;
            $this->ensureTaskIsAvailableToBeAccepted($lockedTask, $worker->id);

            $lockedTask->forceFill([
                'status' => TaskStatusEnum::WORKER_CANCELLED,
                'worker_cancelled_at' => now(),
                'worker_cancel_reason' => $reason,
            ])->save();

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $fromStatus,
                TaskStatusEnum::WORKER_CANCELLED,
                $worker,
                ['worker_id' => $worker->id, 'reason' => $reason]
            );

            return $lockedTask->refresh();
        });
    }

    public function ensureTaskIsAvailableToBeAccepted($lockedTask, $workerId)
    {
        if (! in_array($lockedTask->status, [TaskStatusEnum::ACCEPTED, TaskStatusEnum::IN_PROGRESS], true)) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.cancel_active_only')]);
        }

        if ((int) $lockedTask->assigned_worker_id !== (int) $workerId) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.worker_not_assigned')]);
        }
    }
}
