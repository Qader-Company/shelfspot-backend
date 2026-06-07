<?php

namespace App\Modules\V1\CompaniesWallets\Domain\Repositories;

use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use Illuminate\Pagination\LengthAwarePaginator;

interface CompaniesWalletRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator;

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?CompanyWalletTransaction;

    public function latestTransaction(bool $lockForUpdate = false): ?CompanyWalletTransaction;

    public function currentBalance(): float;

    public function create(array $attributes): CompanyWalletTransaction;

    public function createTransaction(array $attributes, CompanyWalletTransactionTypeEnum $type): CompanyWalletTransaction;

    public function update(CompanyWalletTransaction $companiesWallet, array $attributes): CompanyWalletTransaction;

    public function delete(CompanyWalletTransaction $companiesWallet): void;
}
