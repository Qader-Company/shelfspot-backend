<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminReassignTaskUseCase
{
    public function __construct(private readonly TaskStatusHistoryRecorder $statusHistoryRecorder)
    {
    }

    public function execute(Task $task, Worker $worker, ?User $admin = null): Task
    {
        return DB::transaction(function () use ($task, $worker, $admin) {
            /** @var Task $lockedTask */
            $lockedTask = Task::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            $fromStatus = $lockedTask->status;

            if (! in_array($lockedTask->status, [TaskStatusEnum::WORKER_CANCELLED, TaskStatusEnum::ACCEPTED], true)) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.reassign_cancelled_only')]);
            }

            if (! $worker->is_active) {
                throw ValidationException::withMessages(['worker' => __('tasks.validation.reassign_active_worker_only')]);
            }

            $hasInProgressTask = Task::query()
                ->where('assigned_worker_id', $worker->id)
                ->where('status', TaskStatusEnum::IN_PROGRESS->value)
                ->exists();

            if ($hasInProgressTask) {
                throw ValidationException::withMessages(['worker' => __('tasks.validation.reassign_worker_busy')]);
            }

            $now = now();
            $lockedTask->forceFill([
                'status' => TaskStatusEnum::ACCEPTED,
                'assigned_worker_id' => $worker->id,
                'accepted_at' => $now,
                'start_deadline_at' => $now->copy()->addMinutes(AcceptTaskUseCase::START_DEADLINE_MINUTES),
                'worker_cancelled_at' => null,
                'worker_cancel_reason' => null,
            ])->save();

            $this->statusHistoryRecorder->record(
                task: $lockedTask,
                fromStatus: $fromStatus,
                toStatus: TaskStatusEnum::ACCEPTED,
                actor: $admin,
                meta: ['reassigned_worker_id' => $worker->id]
            );

            return $lockedTask->refresh();
        });
    }
}
