<?php

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Models\Permission;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(FullAccessRoleProvisioner::class)->syncFullAccessRoles();
    }

    public function down(): void
    {
        $permission = Permission::query()
            ->where('portal', PermissionCatalog::ADMIN_PORTAL)
            ->where('name', AdminPermissionEnum::CREATE_COMPANY_USER->value)
            ->first();

        if ($permission) {
            Role::query()
                ->where('portal', PermissionCatalog::ADMIN_PORTAL)
                ->get()
                ->each(fn (Role $role) => $role->revokePermissionTo($permission));

            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
