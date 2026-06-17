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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EloquentManagedAdminRepository implements ManagedAdminRepositoryInterface
{
    public function __construct(private readonly AccessControlRepositoryInterface $accessControlRepository) {}

    public function shelfSpotAdmins(): Collection
    {
        return User::where('type', PortalTypeEnum::ADMIN)->with(['admin', 'roles'])->get();
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

    public function companyAdmins(int $companyId): Collection
    {
        $ids = CompanyUser::where('company_id', $companyId)->pluck('user_id');
        return User::whereIn('id', $ids)->with(['companyUser', 'roles'])->get();
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

    private function protectedRoleNames(string $portal): array
    {
        return match ($portal) {
            PermissionCatalog::ADMIN_PORTAL => [FullAccessRoleProvisioner::SUPER_ADMIN_ROLE],
            PermissionCatalog::COMPANY_PORTAL => [FullAccessRoleProvisioner::COMPANY_OWNER_ROLE],
            default => [],
        };
    }
}
