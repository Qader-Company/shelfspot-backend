<?php

namespace App\Modules\V1\AccessControl\Infrastructure\Persistence\Repositories;

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use App\Modules\V1\AccessControl\Domain\Repositories\ManagedAdminRepositoryInterface;
use App\Modules\V1\Admins\Domain\Models\ShelfSpotAdmin;
use App\Modules\V1\Companies\Domain\Models\CompanyUser;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EloquentManagedAdminRepository implements ManagedAdminRepositoryInterface
{
    public function __construct(private readonly AccessControlRepositoryInterface $accessControlRepository) {}

    public function shelfSpotAdmins(array $filters = []): Collection
    {
        return $this->adminQuery($filters)->get();
    }

    public function createShelfSpotAdmin(array $attributes): User
    {
        return DB::transaction(function () use ($attributes) {
            $user = User::create([...collect($attributes)->only(['name', 'email', 'password'])->all(), 'type' => PortalTypeEnum::ADMIN]);
            ShelfSpotAdmin::create(['user_id' => $user->id, 'is_active' => $attributes['is_active'] ?? true]);
            $this->syncRoles($user, PermissionCatalog::ADMIN_PORTAL, null, $attributes['roles'] ?? []);
            return $user->load(['admin', 'roles']);
        });
    }

    public function updateShelfSpotAdmin(User $user, array $attributes): User
    {
        abort_unless($user->type === PortalTypeEnum::ADMIN, 404);
        return DB::transaction(function () use ($user, $attributes) {
            $user->fill(collect($attributes)->only(['name', 'email', 'password'])->all())->save();
            if (array_key_exists('is_active', $attributes)) {
                $user->admin()->update(['is_active' => $attributes['is_active']]);
            }
            if (array_key_exists('roles', $attributes)) {
                $this->syncRoles($user, PermissionCatalog::ADMIN_PORTAL, null, $attributes['roles']);
            }
            return $user->refresh()->load(['admin', 'roles']);
        });
    }

    public function deleteShelfSpotAdmin(User $user): void
    {
        abort_unless($user->type === PortalTypeEnum::ADMIN, 404);

        $user->loadMissing('roles');
        $this->ensureAdminCanBeDeleted($user);

        DB::transaction(function () use ($user) {
            $user->admin()->delete();
            $user->delete();
        });
    }

    public function companyAdmins(int $companyId, array $filters = []): Collection
    {
        return $this->companyAdminQuery($companyId, $filters)->get();
    }

    public function createCompanyAdmin(int $companyId, array $attributes): User
    {
        return DB::transaction(function () use ($companyId, $attributes) {
            $user = User::create([...collect($attributes)->only(['name', 'email', 'password'])->all(), 'type' => PortalTypeEnum::COMPANY]);
            CompanyUser::create(['company_id' => $companyId, 'user_id' => $user->id, 'is_owner' => false, 'is_active' => $attributes['is_active'] ?? true]);
            $this->syncRoles($user, PermissionCatalog::COMPANY_PORTAL, $companyId, $attributes['roles'] ?? []);
            return $user->load(['companyUser', 'roles']);
        });
    }

    public function updateCompanyAdmin(int $companyId, User $user, array $attributes): User
    {
        $companyUser = CompanyUser::where('company_id', $companyId)->where('user_id', $user->id)->firstOrFail();
        return DB::transaction(function () use ($companyId, $user, $companyUser, $attributes) {
            $user->fill(collect($attributes)->only(['name', 'email', 'password'])->all())->save();
            if (array_key_exists('is_active', $attributes)) {
                $companyUser->update(['is_active' => $attributes['is_active']]);
            }
            if (array_key_exists('roles', $attributes)) {
                $this->syncRoles($user, PermissionCatalog::COMPANY_PORTAL, $companyId, $attributes['roles']);
            }
            return $user->refresh()->load(['companyUser', 'roles']);
        });
    }

    public function deleteCompanyAdmin(int $companyId, User $user): void
    {
        $companyUser = CompanyUser::where('company_id', $companyId)->where('user_id', $user->id)->firstOrFail();

        $user->loadMissing('roles');
        $this->ensureCompanyAdminCanBeDeleted($user, $companyUser);

        DB::transaction(function () use ($user, $companyUser) {
            $companyUser->delete();
            $user->delete();
        });
    }

    /**
     * @throws AuthorizationException
     */
    private function syncRoles(User $user, string $portal, ?int $companyId, array $roleNames): void
    {
        $this->ensureRolesCanBeAssigned($portal, $roleNames);

        $user->syncRoles($this->accessControlRepository->scopedRolesByNames($portal, $companyId, $roleNames));
    }

    /**
     * @throws AuthorizationException
     */
    private function ensureRolesCanBeAssigned(string $portal, array $roleNames): void
    {
        if (empty(array_intersect($roleNames, $this->protectedRoleNames($portal)))) {
            return;
        }

        throw new AuthorizationException('The owner and super admin roles cannot be assigned to managed admins.');
    }

    /**
     * @throws AuthorizationException
     */
    private function ensureAdminCanBeDeleted(User $user): void
    {
        if (! $user->roles->contains('name', FullAccessRoleProvisioner::SUPER_ADMIN_ROLE)) {
            return;
        }

        throw new AuthorizationException('The super admin cannot be deleted.');
    }

    /**
     * @throws AuthorizationException
     */
    private function ensureCompanyAdminCanBeDeleted(User $user, CompanyUser $companyUser): void
    {
        if (! $companyUser->is_owner && ! $user->roles->contains('name', FullAccessRoleProvisioner::COMPANY_OWNER_ROLE)) {
            return;
        }

        throw new AuthorizationException('The company owner cannot be deleted.');
    }

    private function protectedRoleNames(string $portal): array
    {
        return match ($portal) {
            PermissionCatalog::ADMIN_PORTAL => [FullAccessRoleProvisioner::SUPER_ADMIN_ROLE],
            PermissionCatalog::COMPANY_PORTAL => [FullAccessRoleProvisioner::COMPANY_OWNER_ROLE],
            default => [],
        };
    }

    private function adminQuery(array $filters = []): Builder
    {
        return User::query()
            ->where('type', PortalTypeEnum::ADMIN)
            ->with(['admin', 'roles'])
            ->when($this->activeFilter($filters) !== null, fn (Builder $query) => $query->whereHas('admin', fn (Builder $query) => $query->where('is_active', $this->activeFilter($filters))))
            ->when(isset($filters['role']), fn (Builder $query) => $query->whereHas('roles', fn (Builder $query) => $query->where('name', $filters['role'])->where('portal', PermissionCatalog::ADMIN_PORTAL)->where('company_id', null)))
            ->when(isset($filters['search']), fn (Builder $query) => $this->applySearchFilter($query, $filters['search']))
            ->latest();
    }

    private function companyAdminQuery(int $companyId, array $filters = []): Builder
    {
        return User::query()
            ->where('type', PortalTypeEnum::COMPANY)
            ->whereHas('companyUser', fn (Builder $query) => $query->where('company_id', $companyId))
            ->with(['companyUser', 'roles'])
            ->when($this->activeFilter($filters) !== null, fn (Builder $query) => $query->whereHas('companyUser', fn (Builder $query) => $query->where('company_id', $companyId)->where('is_active', $this->activeFilter($filters))))
            ->when(isset($filters['role']), fn (Builder $query) => $query->whereHas('roles', fn (Builder $query) => $query->where('name', $filters['role'])->where('portal', PermissionCatalog::COMPANY_PORTAL)->where('company_id', $companyId)))
            ->when(isset($filters['search']), fn (Builder $query) => $this->applySearchFilter($query, $filters['search']))
            ->latest();
    }

    private function activeFilter(array $filters): mixed
    {
        return $filters['is_active'] ?? $filters['active'] ?? null;
    }

    private function applySearchFilter(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search) {
            $query->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%');
        });
    }
}
