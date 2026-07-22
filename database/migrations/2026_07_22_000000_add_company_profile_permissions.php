<?php

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Models\Permission;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PermissionCatalog::sync(PermissionCatalog::COMPANY_PORTAL);

        $permissions = Permission::query()
            ->where('portal', PermissionCatalog::COMPANY_PORTAL)
            ->whereIn('name', ['view_company', 'edit_company'])
            ->get();

        Role::query()
            ->where('portal', PermissionCatalog::COMPANY_PORTAL)
            ->where('name', FullAccessRoleProvisioner::COMPANY_OWNER_ROLE)
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permissions));
    }

    public function down(): void
    {
        $permissions = Permission::query()
            ->where('portal', PermissionCatalog::COMPANY_PORTAL)
            ->whereIn('name', ['view_company', 'edit_company'])
            ->get();

        Role::query()
            ->where('portal', PermissionCatalog::COMPANY_PORTAL)
            ->where('name', FullAccessRoleProvisioner::COMPANY_OWNER_ROLE)
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permissions));
    }
};
