<?php

namespace App\Modules\V1\AccessControl\Infrastructure\Persistence\Repositories;

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use App\Modules\V1\AccessControl\Domain\Repositories\ManagedAdminRepositoryInterface;
use App\Modules\V1\Admins\Domain\Models\ShelfSpotAdmin;
use App\Modules\V1\Users\Application\Services\UserAccessRevoker;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EloquentManagedAdminRepository implements ManagedAdminRepositoryInterface
{
    public function __construct(
        private readonly AccessControlRepositoryInterface $accessControlRepository,
        private readonly UserAccessRevoker $userAccessRevoker,
    ) {}

    public function shelfSpotAdmins(array $filters = []): Collection
    {
        return $this->adminQuery($filters)->get();
    }

    public function createShelfSpotAdmin(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $user = User::query()->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
                'type' => PortalTypeEnum::ADMIN,
            ]);

            ShelfSpotAdmin::query()->create([
                'user_id' => $user->id,
                'is_active' => $attributes['is_active'] ?? true,
            ]);

            $this->syncRoles($user, $attributes['roles'] ?? []);

            return $user->load(['admin', 'roles']);
        });
    }

    public function updateShelfSpotAdmin(User $user, array $attributes): User
    {
        abort_unless($user->type === PortalTypeEnum::ADMIN, 404);
        $user->loadMissing('roles');
        $this->ensureProtectedAccountCannotBeUpdated($user);

        return DB::transaction(function () use ($user, $attributes): User {
            $willBeDeactivated = array_key_exists('is_active', $attributes)
                && ! (bool) $attributes['is_active'];

            $user->fill(collect($attributes)->only(['name', 'email', 'password'])->all())->save();

            if (array_key_exists('is_active', $attributes)) {
                $user->admin()->update(['is_active' => $attributes['is_active']]);
            }

            if (array_key_exists('roles', $attributes)) {
                $this->syncRoles($user, $attributes['roles']);
            }

            if ($willBeDeactivated) {
                $this->userAccessRevoker->revoke($user);
            }

            return $user->refresh()->load(['admin', 'roles']);
        });
    }

    public function deleteShelfSpotAdmin(User $user): void
    {
        abort_unless($user->type === PortalTypeEnum::ADMIN, 404);

        $user->loadMissing('roles');
        $this->ensureAdminCanBeDeleted($user);

        DB::transaction(function () use ($user): void {
            $this->userAccessRevoker->revoke($user);
            $user->admin()->delete();
            $user->delete();
        });
    }

    /** @throws AuthorizationException */
    private function syncRoles(User $user, array $roleNames): void
    {
        if (in_array(FullAccessRoleProvisioner::SUPER_ADMIN_ROLE, $roleNames, true)) {
            throw new AuthorizationException('The super admin role cannot be assigned to managed admins.');
        }

        $user->syncRoles($this->accessControlRepository->scopedRolesByNames(
            PermissionCatalog::ADMIN_PORTAL,
            null,
            $roleNames,
        ));
    }

    /** @throws AuthorizationException */
    private function ensureAdminCanBeDeleted(User $user): void
    {
        if ($user->roles->contains('name', FullAccessRoleProvisioner::SUPER_ADMIN_ROLE)) {
            throw new AuthorizationException('The super admin cannot be deleted.');
        }
    }

    /** @throws AuthorizationException */
    private function ensureProtectedAccountCannotBeUpdated(User $user): void
    {
        if ($user->roles->contains('name', FullAccessRoleProvisioner::SUPER_ADMIN_ROLE)) {
            throw new AuthorizationException('The super admin account cannot be modified.');
        }
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
