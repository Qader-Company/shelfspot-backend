<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\CompaniesWallets\Domain\Repositories\CompaniesWalletRepositoryInterface;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\CanChargeTaskWalletRule;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use Illuminate\Support\Facades\DB;

class ChargeTaskWalletUseCase
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

            CanChargeTaskWalletRule::validate($lockedTask);

            $transaction = $this->walletRepository->createTransaction([
                'company_id' => $lockedTask->company_id,
                'amount' => $lockedTask->total_price,
                'description' => __('company.wallet.tasks.payment_description', ['task' => $lockedTask->id]),
                'performed_by' => $performedBy,
                'reference_type' => $lockedTask->getMorphClass(),
                'reference_id' => $lockedTask->id,
            ], CompanyWalletTransactionTypeEnum::TASK_PAYMENT);

            $lockedTask->forceFill([
                'payment_status' => TaskPaymentStatusEnum::CHARGED,
                'charged_at' => now(),
            ])->save();

            return $transaction;
        });
    }
}
