<?php

namespace App\Modules\V1\CompaniesWallets\Infrastructure\Persistence\Repositories;

use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\CompaniesWallets\Domain\Repositories\CompaniesWalletRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EloquentCompaniesWalletRepository implements CompaniesWalletRepositoryInterface
{

    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->query($relations, $relationsCount, $filters)
            ->latest()
            ->paginate(request('per_page', 15));
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?CompanyWalletTransaction
    {
        return $this->query($relations, $relationsCount)
            ->where('id', $id)
            ->first();
    }

    public function create(array $attributes): CompanyWalletTransaction
    {
        return DB::transaction(function () use ($attributes) {
            $companiesWallet = CompanyWalletTransaction::create($attributes);

            return $companiesWallet;
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


}
