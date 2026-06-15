<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminReassignTaskUseCase
{
    public function __construct(private readonly TaskRepositoryInterface $taskRepository)
    {
    }

    public function execute(Task $task, Worker $worker, ?User $admin = null): Task
    {
        return DB::transaction(function () use ($task, $worker, $admin) {
            /** @var Task $lockedTask */
            $lockedTask = $this->taskRepository
                ->query()
                ->whereKey($task->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = $lockedTask->status;

            $this->ensureTaskIsAvailableToBeReassigned($lockedTask, $worker->id);

            $hasInProgressTask = $this->taskRepository->query()
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

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $fromStatus,
                TaskStatusEnum::ACCEPTED,
                $worker,
                ['reassigned_worker_id' => $worker->id]
            );

            return $lockedTask->refresh();
        });
    }

    public function ensureTaskIsAvailableToBeReassigned($lockedTask, $workerId)
    {
        if (! in_array($lockedTask->status, [TaskStatusEnum::WORKER_CANCELLED, TaskStatusEnum::ACCEPTED], true)) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.reassign_cancelled_only')]);
        }

        if (! $workerId) {
            throw ValidationException::withMessages(['worker' => __('tasks.validation.reassign_active_worker_only')]);
        }
    }
}
