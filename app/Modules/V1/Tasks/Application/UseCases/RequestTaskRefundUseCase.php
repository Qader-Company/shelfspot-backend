<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestTaskRefundUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskStatusHistoryRecorder $statusHistoryRecorder,
    ) {
    }

    public function execute(Task $task, User $actor): Task
    {
        return DB::transaction(function () use ($task, $actor) {
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

            if ($lockedTask->status === TaskStatusEnum::REFUND_REQUESTED) {
                return $lockedTask->load($this->taskRepository->relations());
            }

            if (! in_array($lockedTask->status, [TaskStatusEnum::PENDING, TaskStatusEnum::FAILED], true)) {
                throw ValidationException::withMessages([
                    'task' => __('tasks.validation.refund_request_pending_or_failed_only'),
                ]);
            }

            if ($lockedTask->payment_status !== TaskPaymentStatusEnum::CHARGED) {
                throw ValidationException::withMessages([
                    'task' => __('company.wallet.tasks.not_charged'),
                ]);
            }

            $fromStatus = $lockedTask->status;

            $lockedTask->forceFill([
                'status' => TaskStatusEnum::REFUND_REQUESTED,
            ])->save();

            $this->statusHistoryRecorder->record(
                task: $lockedTask,
                fromStatus: $fromStatus,
                toStatus: TaskStatusEnum::REFUND_REQUESTED,
                actor: $actor,
                meta: ['payment_status' => $lockedTask->payment_status?->value]
            );

            return $lockedTask->refresh()->load($this->taskRepository->relations());
        });
    }
}
