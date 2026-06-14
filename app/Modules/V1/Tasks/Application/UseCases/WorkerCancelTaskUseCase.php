<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkerCancelTaskUseCase
{
    public function __construct(private readonly TaskStatusHistoryRecorder $statusHistoryRecorder)
    {
    }

    public function execute(Task $task, Worker $worker, string $reason): Task
    {
        return DB::transaction(function () use ($task, $worker, $reason) {
            /** @var Task $lockedTask */
            $lockedTask = Task::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $lockedTask->status;

            if (! in_array($lockedTask->status, [TaskStatusEnum::ACCEPTED, TaskStatusEnum::IN_PROGRESS], true)) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.cancel_active_only')]);
            }

            if ((int) $lockedTask->assigned_worker_id !== (int) $worker->id) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.worker_not_assigned')]);
            }

            $lockedTask->forceFill([
                'status' => TaskStatusEnum::WORKER_CANCELLED,
                'worker_cancelled_at' => now(),
                'worker_cancel_reason' => $reason,
            ])->save();

            $this->statusHistoryRecorder->record(
                task: $lockedTask,
                fromStatus: $fromStatus,
                toStatus: TaskStatusEnum::WORKER_CANCELLED,
                actor: $worker->user,
                meta: ['worker_id' => $worker->id, 'reason' => $reason]
            );

            return $lockedTask->refresh();
        });
    }
}
