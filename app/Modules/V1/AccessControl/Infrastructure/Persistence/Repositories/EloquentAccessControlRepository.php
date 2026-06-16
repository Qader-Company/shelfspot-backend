<?php

namespace App\Modules\V1\AccessControl\Infrastructure\Persistence\Repositories;

use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Models\Permission;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentAccessControlRepository implements AccessControlRepositoryInterface
{
    public function permissions(string $portal, ?int $companyId = null): Collection
    {
        PermissionCatalog::sync($portal, $companyId);
        return $this->permissionQuery($portal, $companyId)->get();
    }

    public function roles(string $portal, ?int $companyId = null): Collection
    {
        PermissionCatalog::sync($portal, $companyId);
        return $this->roleQuery($portal, $companyId)->with('permissions')->get();
    }

    public function createRole(string $portal, ?int $companyId, array $attributes): Role
    {
        PermissionCatalog::sync($portal, $companyId);
        $role = Role::firstOrCreate(['name' => $attributes['name'], 'guard_name' => 'web', 'portal' => $portal, 'company_id' => $companyId]);
        $this->syncRolePermissions($role, $portal, $companyId, $attributes['permissions'] ?? []);
        return $role->load('permissions');
    }

    public function updateRole(string $portal, ?int $companyId, int $roleId, array $attributes): Role
    {
        PermissionCatalog::sync($portal, $companyId);
        $role = $this->roleQuery($portal, $companyId)->findOrFail($roleId);
        $role->fill(collect($attributes)->only('name')->all())->save();
        if (array_key_exists('permissions', $attributes)) {
            $this->syncRolePermissions($role, $portal, $companyId, $attributes['permissions']);
        }
        return $role->load('permissions');
    }

    public function deleteRole(string $portal, ?int $companyId, int $roleId): void
    {
        $this->roleQuery($portal, $companyId)->findOrFail($roleId)->delete();
    }

    public function scopedRolesByNames(string $portal, ?int $companyId, array $names): Collection
    {
        return $this->roleQuery($portal, $companyId)->whereIn('name', $names)->get();
    }

    private function syncRolePermissions(Role $role, string $portal, ?int $companyId, array $permissionNames): void
    {
        $role->syncPermissions($this->permissionQuery($portal, $companyId)->whereIn('name', $permissionNames)->get());
    }

    private function roleQuery(string $portal, ?int $companyId)
    {
        return Role::query()->where('portal', $portal)->where('company_id', $companyId);
    }

    private function permissionQuery(string $portal, ?int $companyId)
    {
        return Permission::query()->where('portal', $portal)->where('company_id', $companyId);
    }
}
