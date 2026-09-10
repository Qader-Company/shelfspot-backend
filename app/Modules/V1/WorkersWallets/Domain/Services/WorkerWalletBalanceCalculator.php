<?php

namespace App\Modules\V1\WorkersWallets\Domain\Services;

use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WorkerWalletTransactionTypeEnum;
use InvalidArgumentException;

class WorkerWalletBalanceCalculator
{
    public static function calculate(
        float|int|string $currentBalance,
        float|int|string $amount,
        WorkerWalletTransactionTypeEnum $type,
    ): float {
        $currentBalance = (float) $currentBalance;
        $amount = (float) $amount;

        $balance = match ($type) {
            WorkerWalletTransactionTypeEnum::TASK_EARNING,
            WorkerWalletTransactionTypeEnum::WITHDRAWAL_REFUND,
            WorkerWalletTransactionTypeEnum::ADJUSTMENT => $currentBalance + $amount,
            WorkerWalletTransactionTypeEnum::WITHDRAWAL => $currentBalance - $amount,
        };

        if ($balance < 0) {
            throw new InvalidArgumentException(__('worker_wallet.insufficient_balance'));
        }

        return round($balance, 2);
    }
}
