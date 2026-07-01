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
use InvalidArgumentException;

class PayDraftTaskUseCase
{
    public function __construct(
        private readonly ChargeTaskWalletUseCase $chargeTaskWalletUseCase,
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly TaskStatusHistoryRecorder $statusHistoryRecorder,
    ) {
    }

    public function execute(Task $task, User $actor): Task
    {
        return DB::transaction(function () use ($task, $actor) {
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

            if ($lockedTask->status === TaskStatusEnum::PENDING
                && $lockedTask->payment_status === TaskPaymentStatusEnum::CHARGED) {
                return $lockedTask->load($this->taskRepository->relations());
            }

            if ($lockedTask->status !== TaskStatusEnum::DRAFT) {
                throw ValidationException::withMessages([
                    'task' => __('tasks.validation.pay_draft_only'),
                ]);
            }

            try {
                $this->chargeTaskWalletUseCase->execute($lockedTask, $actor->id);
            } catch (InvalidArgumentException $exception) {
                $lockedTask->forceFill([
                    'payment_status' => TaskPaymentStatusEnum::FAILED,
                ])->save();

                throw ValidationException::withMessages([
                    'wallet' => $exception->getMessage(),
                ]);
            }

            $lockedTask->refresh();
            $fromStatus = $lockedTask->status;

            $lockedTask->forceFill([
                'status' => TaskStatusEnum::PENDING,
            ])->save();

            $this->statusHistoryRecorder->record(
                task: $lockedTask,
                fromStatus: $fromStatus,
                toStatus: TaskStatusEnum::PENDING,
                actor: $actor,
                meta: ['payment_status' => $lockedTask->payment_status?->value]
            );

            return $lockedTask->refresh()->load($this->taskRepository->relations());
        });
    }
}
