<?php

namespace App\Modules\V1\Admins\Application\Services;

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use App\Modules\V1\Admins\Domain\Repositories\AdminRepositoryInterface;
use App\Modules\V1\Users\Application\Services\UserAccessRevoker;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ShelfSpotAdminManagementService
{
    public function __construct(
        private readonly AdminRepositoryInterface $admins,
        private readonly UserRepositoryInterface $users,
        private readonly AccessControlRepositoryInterface $accessControl,
        private readonly UserAccessRevoker $accessRevoker,
    ) {}

    public function admins(array $filters = []): Collection
    {
        return $this->admins->admins($filters);
    }

    public function create(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $user = $this->users->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
                'type' => PortalTypeEnum::ADMIN,
            ]);

            $this->admins->createProfile($user, $attributes['is_active'] ?? true);
            $this->syncRoles($user, $attributes['roles'] ?? []);

            return $user->load(['admin', 'roles']);
        });
    }

    public function update(User $user, array $attributes): User
    {
        abort_unless($user->type === PortalTypeEnum::ADMIN, 404);
        $admin = $this->admins->profileFor($user);
        $user->loadMissing('roles');
        $this->ensureProtectedAccountCannotBeUpdated($user);

        return DB::transaction(function () use ($user, $admin, $attributes): User {
            $willBeDeactivated = array_key_exists('is_active', $attributes)
                && ! (bool) $attributes['is_active'];

            $this->users->update($user, collect($attributes)->only(['name', 'email', 'password'])->all());

            if (array_key_exists('is_active', $attributes)) {
                $this->admins->updateProfile($admin, ['is_active' => $attributes['is_active']]);
            }

            if (array_key_exists('roles', $attributes)) {
                $this->syncRoles($user, $attributes['roles']);
            }

            if ($willBeDeactivated) {
                $this->accessRevoker->revoke($user);
            }

            return $user->refresh()->load(['admin', 'roles']);
        });
    }

    public function delete(User $user): void
    {
        abort_unless($user->type === PortalTypeEnum::ADMIN, 404);
        $admin = $this->admins->profileFor($user);
        $user->loadMissing('roles');
        $this->ensureAdminCanBeDeleted($user);

        DB::transaction(function () use ($user, $admin): void {
            $this->accessRevoker->revoke($user);
            $this->admins->deleteProfile($admin);
            $this->users->delete($user);
        });
    }

    /** @throws AuthorizationException */
    private function syncRoles(User $user, array $roleNames): void
    {
        if (in_array(FullAccessRoleProvisioner::SUPER_ADMIN_ROLE, $roleNames, true)) {
            throw new AuthorizationException('The super admin role cannot be assigned to managed admins.');
        }

        $user->syncRoles($this->accessControl->scopedRolesByNames(
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
}
