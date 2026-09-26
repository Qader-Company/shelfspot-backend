<?php

namespace App\Modules\V1\CompanyAdmins\Infrastructure\Persistence\Repositories;

use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;
use App\Modules\V1\CompanyAdmins\Domain\Repositories\CompanyUserRepositoryInterface;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentCompanyUserRepository implements CompanyUserRepositoryInterface
{
    public function users(int $companyId, array $filters = []): Collection
    {
        return $this->userQuery($companyId, $filters)->get();
    }

    public function findUser(int $companyId, int $userId): User
    {
        return $this->userQuery($companyId)
            ->whereKey($userId)
            ->firstOrFail();
    }

    public function profileFor(int $companyId, User $user): CompanyUser
    {
        return CompanyUser::query()
            ->where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    public function create(array $attributes): CompanyUser
    {
        return CompanyUser::query()->create($attributes);
    }

    public function update(CompanyUser $companyUser, array $attributes): CompanyUser
    {
        $companyUser->fill($attributes)->save();

        return $companyUser;
    }

    public function delete(CompanyUser $companyUser): void
    {
        $companyUser->delete();
    }

    private function userQuery(int $companyId, array $filters = []): Builder
    {
        return User::query()
            ->where('type', PortalTypeEnum::COMPANY)
            ->whereHas('companyUser', fn (Builder $query) => $query->where('company_id', $companyId))
            ->with(['companyUser', 'roles'])
            ->when($this->activeFilter($filters) !== null, fn (Builder $query) => $query->whereHas(
                'companyUser',
                fn (Builder $query) => $query
                    ->where('company_id', $companyId)
                    ->where('is_active', $this->activeFilter($filters)),
            ))
            ->when(isset($filters['role']), fn (Builder $query) => $query->whereHas(
                'roles',
                fn (Builder $query) => $query
                    ->where('name', $filters['role'])
                    ->where('portal', PermissionCatalog::COMPANY_PORTAL)
                    ->where('company_id', $companyId),
            ))
            ->when(isset($filters['search']), function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    $query->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('email', 'like', '%'.$filters['search'].'%');
                });
            })
            ->latest();
    }

    private function activeFilter(array $filters): mixed
    {
        return $filters['is_active'] ?? $filters['active'] ?? null;
    }
}
