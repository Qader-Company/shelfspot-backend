<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\CompaniesWallets\Domain\Repositories\CompaniesWalletRepositoryInterface;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChargeTaskWalletUseCase
{
    public function __construct(private readonly CompaniesWalletRepositoryInterface $walletRepository)
    {
    }

    public function execute(Task $task): CompanyWalletTransaction
    {
        if ($task->company_id === null) {
            throw ValidationException::withMessages([
                'task' => __('company.wallet.tasks.company_required'),
            ]);
        }

        if ($task->payment_status === TaskPaymentStatusEnum::CHARGED) {
            throw ValidationException::withMessages([
                'task' => __('company.wallet.tasks.already_charged'),
            ]);
        }

        return DB::transaction(function () use ($task) {
            $transaction = $this->walletRepository->createTransaction([
                'company_id' => $task->company_id,
                'amount' => $task->total_price,
                'description' => __('company.wallet.tasks.payment_description', ['task' => $task->id]),
                'performed_by' => auth()->id(),
                'reference_type' => $task->getMorphClass(),
                'reference_id' => $task->id,
            ], CompanyWalletTransactionTypeEnum::TASK_PAYMENT);

            $task->forceFill([
                'payment_status' => TaskPaymentStatusEnum::CHARGED,
                'charged_at' => now(),
            ])->save();

            return $transaction;
        });
    }
}
