<?php

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Models\Permission;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        CompanyPermissionEnum::VIEW_STORE,
        CompanyPermissionEnum::CREATE_STORE,
        CompanyPermissionEnum::EDIT_STORE,
        CompanyPermissionEnum::DELETE_STORE,
    ];

    public function up(): void
    {
        app(FullAccessRoleProvisioner::class)->syncFullAccessRoles();
    }

    public function down(): void
    {
        $permissions = Permission::query()
            ->where('portal', PermissionCatalog::COMPANY_PORTAL)
            ->whereIn('name', array_map(fn (CompanyPermissionEnum $permission) => $permission->value, self::PERMISSIONS))
            ->get();

        Role::query()
            ->where('portal', PermissionCatalog::COMPANY_PORTAL)
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permissions));

        Permission::query()->whereKey($permissions->modelKeys())->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
