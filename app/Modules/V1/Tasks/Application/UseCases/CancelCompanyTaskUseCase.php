<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\CompaniesWallets\Domain\Repositories\CompaniesWalletRepositoryInterface;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\CanCompanyCancelTaskRule;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Support\Facades\DB;

class CancelCompanyTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly CompaniesWalletRepositoryInterface $walletRepository,
    ) {}

    public function execute(Task $task, User $actor): Task
    {
        return DB::transaction(function () use ($task, $actor) {
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

            if ($lockedTask->status === TaskStatusEnum::COMPANY_CANCELLED
                && $lockedTask->payment_status === TaskPaymentStatusEnum::REFUNDED) {
                return $lockedTask->load($this->taskRepository->relations());
            }

            CanCompanyCancelTaskRule::validate($lockedTask);

            $fromStatus = $lockedTask->status;
            $lockedTask->forceFill([
                'status' => TaskStatusEnum::COMPANY_CANCELLED,
            ])->save();

            $this->refundWallet($lockedTask, $actor->id);

            $lockedTask->forceFill([
                'payment_status' => TaskPaymentStatusEnum::REFUNDED,
            ])->save();

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $fromStatus,
                TaskStatusEnum::COMPANY_CANCELLED,
                $actor,
                ['actor_type' => 'company', 'payment_status' => TaskPaymentStatusEnum::REFUNDED->value]
            );

            return $lockedTask->refresh()->load($this->taskRepository->relations());
        });
    }

    private function refundWallet(Task $task, int $performedBy): void
    {
        $referenceType = $task->getMorphClass();
        $referenceId = (int) $task->id;

        $this->walletRepository->createTransaction([
            'company_id' => $task->company_id,
            'amount' => $task->total_price,
            'description' => __('company.wallet.tasks.refund_description', ['task' => $task->id]),
            'performed_by' => $performedBy,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ], CompanyWalletTransactionTypeEnum::TASK_REFUND);
    }
}
