<?php

namespace App\Modules\V1\Companies\Infrastructure\Persistence\Repositories;

use App\Modules\Shared\Infrastructure\Persistence\Repositories\HandlesTrash;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\Repositories\CompanyRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentCompanyRepository implements CompanyRepositoryInterface
{
    use HandlesTrash;

    protected function trashableModel(): string
    {
        return Company::class;
    }
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = [],): LengthAwarePaginator
    {
        return $this->query(
            $relations,
            $relationsCount,
            $filters,
        )->paginate();
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Company
    {
        return $this->query(
            relations: $relations,
            relationsCount: $relationsCount,
        )->find($id);
    }

    public function create(array $attributes): Company
    {
        return DB::transaction(function () use ($attributes) {
            return Company::create($attributes);
        });
    }

    public function update(Company $company, array $attributes): Company
    {
        return DB::transaction(function () use ($company, $attributes) {
            $company->update($attributes);

            if (array_key_exists('is_active', $attributes) && ! (bool) $attributes['is_active']) {
                $this->revokeCompanyUserTokens($company);
            }

            return $company;
        });
    }

    public function delete(Company $company): void
    {
        DB::transaction(function () use ($company) {
            $this->revokeCompanyUserTokens($company);
            $company->delete();
        });
    }

    private function query(array $relations, array $relationsCount, array $filters = [])
    {
        return Company::when($filters, fn($q) => $q->filter($filters))
            ->when($relations, fn($q) => $q->with($relations))
            ->when($relationsCount, fn($q) => $q->withCount($relationsCount));
    }

    private function revokeCompanyUserTokens(Company $company): void
    {
        $company->users()
            ->with('user')
            ->get()
            ->each(fn ($companyUser) => $companyUser->user?->tokens()->delete());
    }
}
