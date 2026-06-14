<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptTaskUseCase
{
    public const START_DEADLINE_MINUTES = 15;

    public function __construct(
        private readonly TaskStatusHistoryRecorder $statusHistoryRecorder,
        private readonly TaskRepositoryInterface $taskRepository,
    )
    {
    }

    public function execute(Task $task, Worker $worker): Task
    {
        return DB::transaction(function () use ($task, $worker) {
            /** @var Task $lockedTask */
            $lockedTask = $this->taskRepository->query()
                ->whereKey($task->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureTaskIsAvailableToBeAccepted($lockedTask);

            $now = now();
            $fromStatus = $lockedTask->status;
            $lockedTask->forceFill([
                'status' => TaskStatusEnum::ACCEPTED,
                'assigned_worker_id' => $worker->id,
                'accepted_at' => $now,
                'start_deadline_at' => $now->copy()->addMinutes(self::START_DEADLINE_MINUTES),
            ])->save();

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $fromStatus,
                TaskStatusEnum::ACCEPTED,
                $worker,
                [
                    'worker_id' => $worker->id,
                    'start_deadline_minutes' => self::START_DEADLINE_MINUTES
                ]
            );

            return $lockedTask->refresh();
        });
    }

    private function ensureTaskIsAvailableToBeAccepted(Task $lockedTask): void
    {
        if ($lockedTask->status !== TaskStatusEnum::PENDING) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.accept_pending_only')]);
        }

        if ($lockedTask->payment_status !== TaskPaymentStatusEnum::CHARGED) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.accept_charged_only')]);
        }

        if ($lockedTask->company_deleted_at !== null) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.accept_deleted_task')]);
        }

        if ($lockedTask->assigned_worker_id !== null) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.accept_unassigned_only')]);
        }

        if (! $lockedTask->date->isSameDay(now())) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.accept_execution_date_only')]);
        }
    }
}
