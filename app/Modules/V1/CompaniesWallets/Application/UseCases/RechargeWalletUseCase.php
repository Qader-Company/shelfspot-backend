<?php

namespace App\Modules\V1\CompaniesWallets\Application\UseCases;

use App\Modules\V1\CompaniesWallets\Domain\Repositories\CompaniesWalletRepositoryInterface;
use App\Modules\V1\CompaniesWallets\Domain\Services\WalletBalanceCalculator;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;

class RechargeWalletUseCase
{

    public function __construct(readonly private CompaniesWalletRepositoryInterface $walletRepository)
    {
    }

    public function execute(array $data): mixed
    {
        $balanceAfter = WalletBalanceCalculator::calculateBalance(
            $data['amount'],
            CompanyWalletTransactionTypeEnum::ADMIN_GRANT
        );
        $this->walletRepository->create([
            'amount' => $data['amount'],
            'type' => CompanyWalletTransactionTypeEnum::ADMIN_GRANT,
            'description' => 'Recharge Wallet',
            'performed_by' => auth()->id(),
            'balance_after' => $balanceAfter,
        ]);
        return $balanceAfter;
    }
}
