<?php

namespace App\Modules\V1\AccessControl\Application\Services;

use App\Modules\V1\AccessControl\Domain\Models\Permission;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\Users\Domain\Models\User;
use Spatie\Permission\PermissionRegistrar;

class FullAccessRoleProvisioner
{
    public const SUPER_ADMIN_ROLE = 'super_admin';

    public const COMPANY_OWNER_ROLE = 'owner';

    public function ensureSuperAdminRole(): Role
    {
        return $this->ensureFullAccessRole(
            self::SUPER_ADMIN_ROLE,
            PermissionCatalog::ADMIN_PORTAL,
            null,
        );
    }

    public function ensureCompanyOwnerRole(int $companyId): Role
    {
        return $this->ensureFullAccessRole(
            self::COMPANY_OWNER_ROLE,
            PermissionCatalog::COMPANY_PORTAL,
            $companyId,
        );
    }

    public function assignSuperAdminRole(User $user): void
    {
        $this->assignRole($user, $this->ensureSuperAdminRole());
    }

    public function assignCompanyOwnerRole(User $user, int $companyId): void
    {
        $this->assignRole($user, $this->ensureCompanyOwnerRole($companyId));
    }

    /**
     * Refresh the permissions of every existing protected full-access role.
     *
     * This keeps previously seeded roles up to date when a permission is added
     * to either permission enum and the seeder is run again.
     */
    public function syncFullAccessRoles(): void
    {
        foreach ([
            PermissionCatalog::ADMIN_PORTAL => self::SUPER_ADMIN_ROLE,
            PermissionCatalog::COMPANY_PORTAL => self::COMPANY_OWNER_ROLE,
        ] as $portal => $roleName) {
            PermissionCatalog::sync($portal);

            $permissions = Permission::query()
                ->where('portal', $portal)
                ->get();

            Role::query()
                ->where('portal', $portal)
                ->where('name', $roleName)
                ->each(fn (Role $role) => $role->syncPermissions($permissions));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function ensureFullAccessRole(string $name, string $portal, ?int $companyId): Role
    {
        PermissionCatalog::sync($portal);

        $role = Role::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
            'portal' => $portal,
            'company_id' => $companyId,
        ]);

        $role->syncPermissions(
            Permission::query()
                ->where('portal', $portal)
                ->get()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role->load('permissions');
    }

    private function assignRole(User $user, Role $role): void
    {
        if (! $user->roles()->whereKey($role->getKey())->exists()) {
            $user->assignRole($role);
        }
    }
}
