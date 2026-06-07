<?php

namespace App\Modules\V1\CompaniesWallets\Domain\Services;

use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use InvalidArgumentException;

class WalletBalanceCalculator
{
    public static function calculateBalance(float|int|string $currentBalance, float|int|string $amount, CompanyWalletTransactionTypeEnum $type): float
    {
        $currentBalance = (float) $currentBalance;
        $amount = (float) $amount;

        $balance = match ($type) {
            CompanyWalletTransactionTypeEnum::ADMIN_GRANT,
            CompanyWalletTransactionTypeEnum::COUPON_REDEMPTION,
            CompanyWalletTransactionTypeEnum::TASK_REFUND => $currentBalance + $amount,
            CompanyWalletTransactionTypeEnum::TASK_PAYMENT => $currentBalance - $amount,
            CompanyWalletTransactionTypeEnum::ADJUSTMENT => $currentBalance + $amount,
        };

        if ($balance < 0) {
            throw new InvalidArgumentException(__('company.wallet.insufficient_balance'));
        }

        return round($balance, 2);
    }
}
