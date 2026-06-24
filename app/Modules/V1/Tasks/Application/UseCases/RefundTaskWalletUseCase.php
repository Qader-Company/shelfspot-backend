<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\CompaniesWallets\Domain\Repositories\CompaniesWalletRepositoryInterface;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\CanRefundTaskWalletRule;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use Illuminate\Support\Facades\DB;

class RefundTaskWalletUseCase
{
    public function __construct(
        private readonly CompaniesWalletRepositoryInterface $walletRepository,
        private readonly TaskRepositoryInterface $taskRepository
    ) {
    }

    public function execute(Task $task, ?int $performedBy = null): CompanyWalletTransaction
    {
        return DB::transaction(function () use ($task, $performedBy) {
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

            CanRefundTaskWalletRule::validate($lockedTask);

            $transaction = $this->walletRepository->createTransaction([
                'company_id' => $lockedTask->company_id,
                'amount' => $lockedTask->total_price,
                'description' => __('company.wallet.tasks.refund_description', ['task' => $lockedTask->id]),
                'performed_by' => $performedBy,
                'reference_type' => $lockedTask->getMorphClass(),
                'reference_id' => $lockedTask->id,
            ], CompanyWalletTransactionTypeEnum::TASK_REFUND);

            $lockedTask->forceFill([
                'payment_status' => TaskPaymentStatusEnum::REFUNDED,
            ])->save();

            return $transaction;
        });
    }
}
