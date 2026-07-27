<?php

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Models\Permission;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PermissionCatalog::sync(PermissionCatalog::ADMIN_PORTAL);

        $permission = Permission::query()
            ->where('portal', PermissionCatalog::ADMIN_PORTAL)
            ->where('name', AdminPermissionEnum::VIEW_DASHBOARD->value)
            ->first();

        if ($permission === null) {
            return;
        }

        Role::query()
            ->where('portal', PermissionCatalog::ADMIN_PORTAL)
            ->where('name', FullAccessRoleProvisioner::SUPER_ADMIN_ROLE)
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));
    }

    public function down(): void
    {
        $permission = Permission::query()
            ->where('portal', PermissionCatalog::ADMIN_PORTAL)
            ->where('name', AdminPermissionEnum::VIEW_DASHBOARD->value)
            ->first();

        if ($permission === null) {
            return;
        }

        Role::query()
            ->where('portal', PermissionCatalog::ADMIN_PORTAL)
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permission));

        $permission->delete();
    }
};
