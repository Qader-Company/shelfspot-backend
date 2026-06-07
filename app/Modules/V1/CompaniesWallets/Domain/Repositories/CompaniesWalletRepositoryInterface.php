<?php

namespace App\Modules\V1\CompaniesWallets\Domain\Repositories;

use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

interface CompaniesWalletRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator;

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?CompanyWalletTransaction;

    public function create(array $attributes): CompanyWalletTransaction;

    public function update(CompanyWalletTransaction $companiesWallet, array $attributes): CompanyWalletTransaction;

    public function delete(CompanyWalletTransaction $companiesWallet): void;
}
