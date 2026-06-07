<?php

namespace App\Modules\V1\CompaniesWallets\Domain\Services;

use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;

class WalletBalanceCalculator
{
    public static function calculateBalance(int $amount, CompanyWalletTransactionTypeEnum $type): int
    {
        $latestTransaction = CompanyWalletTransaction::latest()->first() ?? 0;
        return match ($type){
            CompanyWalletTransactionTypeEnum::ADMIN_GRANT => static::addToBalance($latestTransaction, $amount),
            CompanyWalletTransactionTypeEnum::COUPON_REDEMPTION => static::addToBalance($latestTransaction, $amount),
            CompanyWalletTransactionTypeEnum::TASK_PAYMENT => static::subtractFromBalance($latestTransaction, $amount),
            CompanyWalletTransactionTypeEnum::TASK_REFUND => static::addToBalance($latestTransaction, $amount),
        };
    }

    private static function addToBalance($latestTransaction, $amount): int
    {
        return $latestTransaction + $amount;
    }

    private static function subtractFromBalance($latestTransaction, $amount): int
    {
        return $latestTransaction - $amount;
    }
}
