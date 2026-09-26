<?php

namespace App\Modules\V1\Admins\Infrastructure\Persistence\Repositories;

use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\Admins\Domain\Models\ShelfSpotAdmin;
use App\Modules\V1\Admins\Domain\Repositories\AdminRepositoryInterface;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentAdminRepository implements AdminRepositoryInterface
{
    public function admins(array $filters = []): Collection
    {
        return $this->adminQuery($filters)->get();
    }

    public function profileFor(User $user): ShelfSpotAdmin
    {
        return ShelfSpotAdmin::query()
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    public function createProfile(User $user, bool $isActive = true): ShelfSpotAdmin
    {
        return ShelfSpotAdmin::query()->create([
            'user_id' => $user->id,
            'is_active' => $isActive,
        ]);
    }

    public function updateProfile(ShelfSpotAdmin $admin, array $attributes): ShelfSpotAdmin
    {
        $admin->fill($attributes)->save();

        return $admin;
    }

    public function deleteProfile(ShelfSpotAdmin $admin): void
    {
        $admin->delete();
    }

    private function adminQuery(array $filters = []): Builder
    {
        return User::query()
            ->where('type', PortalTypeEnum::ADMIN)
            ->with(['admin', 'roles'])
            ->when($this->activeFilter($filters) !== null, fn (Builder $query) => $query->whereHas(
                'admin',
                fn (Builder $query) => $query->where('is_active', $this->activeFilter($filters)),
            ))
            ->when(isset($filters['role']), fn (Builder $query) => $query->whereHas(
                'roles',
                fn (Builder $query) => $query
                    ->where('name', $filters['role'])
                    ->where('portal', PermissionCatalog::ADMIN_PORTAL)
                    ->whereNull('company_id'),
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
