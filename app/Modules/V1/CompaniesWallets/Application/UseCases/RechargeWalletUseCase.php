<?php

namespace App\Modules\V1\CompaniesWallets\Application\UseCases;

use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\CompaniesWallets\Domain\Repositories\CompaniesWalletRepositoryInterface;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;

class RechargeWalletUseCase
{
    public function __construct(readonly private CompaniesWalletRepositoryInterface $walletRepository)
    {
    }

    public function execute(array $data, ?int $performedBy = null): CompanyWalletTransaction
    {
        return $this->walletRepository->createTransaction([
            'amount' => $data['amount'],
            'description' => $data['description'] ?? __('company.wallet.manual_recharge_description'),
            'performed_by' => $performedBy,
        ], CompanyWalletTransactionTypeEnum::ADMIN_GRANT);
    }
}
