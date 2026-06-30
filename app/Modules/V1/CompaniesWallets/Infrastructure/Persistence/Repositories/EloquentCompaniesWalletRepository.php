<?php

namespace App\Modules\V1\CompaniesWallets\Infrastructure\Persistence\Repositories;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\CompaniesWallets\Domain\Repositories\CompaniesWalletRepositoryInterface;
use App\Modules\V1\CompaniesWallets\Domain\Services\WalletBalanceCalculator;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentCompaniesWalletRepository implements CompaniesWalletRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->query($relations, $relationsCount, $filters)
            ->latest()
            ->paginate();
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?CompanyWalletTransaction
    {
        return $this->query($relations, $relationsCount)
            ->where('id', $id)
            ->first();
    }

    public function latestTransaction(?int $companyId = null, bool $lockForUpdate = false): ?CompanyWalletTransaction
    {
        return $this->queryForCompany($companyId)
            ->latest('id')
            ->when($lockForUpdate, fn (Builder $query) => $query->lockForUpdate())
            ->first();
    }

    public function currentBalance(?int $companyId = null): float
    {
        return (float) ($this->latestTransaction($companyId)?->balance_after ?? 0);
    }

    public function findTransactionByReference(
        int $companyId,
        CompanyWalletTransactionTypeEnum $type,
        string $referenceType,
        int $referenceId,
        bool $lockForUpdate = false
    ): ?CompanyWalletTransaction {
        return $this->queryForCompany($companyId)
            ->where('type', $type->value)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->when($lockForUpdate, fn (Builder $query) => $query->lockForUpdate())
            ->first();
    }

    public function create(array $attributes): CompanyWalletTransaction
    {
        return DB::transaction(fn () => CompanyWalletTransaction::create($attributes));
    }

    public function createTransaction(array $attributes, CompanyWalletTransactionTypeEnum $type): CompanyWalletTransaction
    {
        return DB::transaction(function () use ($attributes, $type) {
            $companyId = isset($attributes['company_id'])
                ? (int) $attributes['company_id']
                : app(TenantContextInterface::class)->getCompanyId();

            if ($companyId !== null) {
                Company::withoutGlobalScopes()
                    ->whereKey($companyId)
                    ->lockForUpdate()
                    ->first();
            }

            $currentBalance = (float) ($this->latestTransaction($companyId, lockForUpdate: true)?->balance_after ?? 0);
            $balanceAfter = WalletBalanceCalculator::calculateBalance(
                currentBalance: $currentBalance,
                amount: $attributes['amount'],
                type: $type
            );

            return CompanyWalletTransaction::create(array_merge($attributes, [
                'type' => $type,
                'balance_after' => $balanceAfter,
            ]));
        });
    }

    public function update(CompanyWalletTransaction $companiesWallet, array $attributes): CompanyWalletTransaction
    {
        return DB::transaction(function () use ($companiesWallet, $attributes) {
            $companiesWallet->update($attributes);

            return $companiesWallet;
        });
    }

    public function delete(CompanyWalletTransaction $companiesWallet): void
    {
        $companiesWallet->delete();
    }

    private function query(array $relations = [], array $relationsCount = [], array $filters = []): Builder
    {
        return CompanyWalletTransaction::query()
            ->when($filters, fn (Builder $query) => $query->filter($filters))
            ->when($relations, fn (Builder $query) => $query->with($relations))
            ->when($relationsCount, fn (Builder $query) => $query->withCount($relationsCount));
    }

    private function queryForCompany(?int $companyId = null): Builder
    {
        if ($companyId === null) {
            return CompanyWalletTransaction::query();
        }

        return CompanyWalletTransaction::withoutGlobalScopes()
            ->where('company_id', $companyId);
    }
}
