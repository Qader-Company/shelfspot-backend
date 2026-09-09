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
    private const PERMISSIONS = [
        AdminPermissionEnum::VIEW_COMPANY_USER,
        AdminPermissionEnum::EDIT_COMPANY_USER,
        AdminPermissionEnum::RESET_COMPANY_USER_PASSWORD,
        AdminPermissionEnum::DELETE_COMPANY_USER,
    ];

    public function up(): void
    {
        app(FullAccessRoleProvisioner::class)->syncFullAccessRoles();
    }

    public function down(): void
    {
        $permissions = Permission::query()
            ->where('portal', PermissionCatalog::ADMIN_PORTAL)
            ->whereIn('name', array_map(fn (AdminPermissionEnum $permission) => $permission->value, self::PERMISSIONS))
            ->get();

        Role::query()
            ->where('portal', PermissionCatalog::ADMIN_PORTAL)
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permissions));

        Permission::query()->whereKey($permissions->modelKeys())->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
