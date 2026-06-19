<?php

namespace App\Modules\V1\AccessControl\Infrastructure\Persistence\Repositories;

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Models\Permission;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Auth\Access\AuthorizationException;

class EloquentAccessControlRepository implements AccessControlRepositoryInterface
{
    public function permissions(string $portal, ?int $companyId = null): Collection
    {
        PermissionCatalog::sync($portal);
        return $this->permissionQuery($portal)->get();
    }

    public function roles(string $portal, ?int $companyId = null): Collection
    {
        PermissionCatalog::sync($portal);
        return $this->roleQuery($portal, $companyId)->with('permissions')->get();
    }

    public function createRole(string $portal, ?int $companyId, array $attributes): Role
    {
        PermissionCatalog::sync($portal);
        $this->ensureRoleNameCanBeCreated($portal, $companyId, $attributes['name']);
        $role = Role::firstOrCreate(['name' => $attributes['name'], 'guard_name' => 'web', 'portal' => $portal, 'company_id' => $companyId]);
        $this->syncRolePermissions($role, $portal, $attributes['permissions'] ?? []);
        return $role->load('permissions');
    }

    public function updateRole(string $portal, ?int $companyId, int $roleId, array $attributes): Role
    {
        PermissionCatalog::sync($portal);
        $role = $this->roleQuery($portal, $companyId)->findOrFail($roleId);
        $this->ensureRoleCanBeModified($role);
        $role->fill(collect($attributes)->only('name')->all())->save();
        if (array_key_exists('permissions', $attributes)) {
            $this->syncRolePermissions($role, $portal, $attributes['permissions']);
        }
        return $role->load('permissions');
    }

    public function deleteRole(string $portal, ?int $companyId, int $roleId): void
    {
        $role = $this->roleQuery($portal, $companyId)->findOrFail($roleId);
        $this->ensureRoleCanBeModified($role);
        $role->delete();
    }

    public function scopedRolesByNames(string $portal, ?int $companyId, array $names): Collection
    {
        return $this->roleQuery($portal, $companyId)->whereIn('name', $names)->get();
    }

    /**
     * @throws AuthorizationException
     */
    private function ensureRoleNameCanBeCreated(string $portal, ?int $companyId, string $name): void
    {
        if ($this->isProtectedFullAccessRoleName($portal, $companyId, $name)) {
            throw new AuthorizationException('The owner and super admin roles are managed by the system and cannot be created manually.');
        }
    }

    /**
     * @throws AuthorizationException
     */
    private function ensureRoleCanBeModified(Role $role): void
    {
        if ($this->isProtectedFullAccessRole($role)) {
            throw new AuthorizationException('The owner and super admin roles cannot be modified or deleted.');
        }
    }

    private function isProtectedFullAccessRole(Role $role): bool
    {
        return $this->isProtectedFullAccessRoleName($role->portal, $role->company_id, $role->name);
    }

    private function isProtectedFullAccessRoleName(string $portal, ?int $companyId, string $name): bool
    {
        return ($portal === PermissionCatalog::ADMIN_PORTAL
                && $companyId === null
                && $name === FullAccessRoleProvisioner::SUPER_ADMIN_ROLE)
            || ($portal === PermissionCatalog::COMPANY_PORTAL
                && $companyId !== null
                && $name === FullAccessRoleProvisioner::COMPANY_OWNER_ROLE);
    }

    private function syncRolePermissions(Role $role, string $portal, array $permissionNames): void
    {
        $role->syncPermissions($this->permissionQuery($portal)->whereIn('name', $permissionNames)->get());
    }

    private function roleQuery(string $portal, ?int $companyId)
    {
        return Role::query()->where('portal', $portal)->where('company_id', $companyId);
    }

    private function permissionQuery(string $portal)
    {
        return Permission::query()->where('portal', $portal);
    }
}
