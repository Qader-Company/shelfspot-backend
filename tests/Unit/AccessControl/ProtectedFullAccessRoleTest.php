<?php

namespace Tests\Unit\AccessControl;

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\AccessControl\Infrastructure\Persistence\Repositories\EloquentAccessControlRepository;
use App\Modules\V1\AccessControl\Infrastructure\Persistence\Repositories\EloquentManagedAdminRepository;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Database\Seeders\AccessControlPermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProtectedFullAccessRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_seeder_is_idempotent_and_refreshes_full_access_roles(): void
    {
        $provisioner = app(FullAccessRoleProvisioner::class);
        $superAdminRole = $provisioner->ensureSuperAdminRole();
        $company = Company::query()->create([
            'name' => 'Permission Seeder Company',
            'email' => 'permission-seeder@example.com',
            'phone' => '01000000000',
            'cr_number' => 'PERMISSION-SEEDER-001',
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
        ]);
        $companyOwnerRole = $provisioner->ensureCompanyOwnerRole($company->id);
        $owner = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);
        CompanyUser::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'is_owner' => true,
        ]);

        $superAdminRole->syncPermissions([]);
        $companyOwnerRole->syncPermissions([]);

        app(AccessControlPermissionSeeder::class)->run();
        app(AccessControlPermissionSeeder::class)->run();

        $this->assertDatabaseCount(
            'permissions',
            count(AdminPermissionEnum::cases()) + count(CompanyPermissionEnum::cases())
        );
        $this->assertEqualsCanonicalizing(
            PermissionCatalog::names(PermissionCatalog::ADMIN_PORTAL),
            $superAdminRole->refresh()->permissions()->pluck('name')->all()
        );
        $this->assertEqualsCanonicalizing(
            PermissionCatalog::names(PermissionCatalog::COMPANY_PORTAL),
            $companyOwnerRole->refresh()->permissions()->pluck('name')->all()
        );
        $this->assertTrue($owner->roles()->whereKey($companyOwnerRole)->exists());
    }

    public function test_super_admin_role_cannot_be_created_manually(): void
    {
        $this->expectException(AuthorizationException::class);

        app(EloquentAccessControlRepository::class)->createRole(
            PermissionCatalog::ADMIN_PORTAL,
            null,
            ['name' => FullAccessRoleProvisioner::SUPER_ADMIN_ROLE]
        );
    }

    public function test_super_admin_role_cannot_be_updated(): void
    {
        $role = app(FullAccessRoleProvisioner::class)->ensureSuperAdminRole();

        $this->expectException(AuthorizationException::class);

        app(EloquentAccessControlRepository::class)->updateRole(
            PermissionCatalog::ADMIN_PORTAL,
            null,
            $role->id,
            ['name' => 'changed_super_admin']
        );
    }

    public function test_super_admin_role_cannot_be_deleted(): void
    {
        $role = app(FullAccessRoleProvisioner::class)->ensureSuperAdminRole();

        $this->expectException(AuthorizationException::class);

        app(EloquentAccessControlRepository::class)->deleteRole(
            PermissionCatalog::ADMIN_PORTAL,
            null,
            $role->id
        );
    }

    public function test_company_owner_role_cannot_be_created_manually(): void
    {
        $this->expectException(AuthorizationException::class);

        app(EloquentAccessControlRepository::class)->createRole(
            PermissionCatalog::COMPANY_PORTAL,
            10,
            ['name' => FullAccessRoleProvisioner::COMPANY_OWNER_ROLE]
        );
    }

    public function test_company_owner_role_cannot_be_updated(): void
    {
        $companyId = 10;
        $role = app(FullAccessRoleProvisioner::class)->ensureCompanyOwnerRole($companyId);

        $this->expectException(AuthorizationException::class);

        app(EloquentAccessControlRepository::class)->updateRole(
            PermissionCatalog::COMPANY_PORTAL,
            $companyId,
            $role->id,
            ['name' => 'changed_owner']
        );
    }

    public function test_super_admin_role_cannot_be_assigned_to_new_admin(): void
    {
        app(FullAccessRoleProvisioner::class)->ensureSuperAdminRole();

        $this->expectException(AuthorizationException::class);

        app(EloquentManagedAdminRepository::class)->createShelfSpotAdmin([
            'name' => 'Second Super Admin',
            'email' => 'second-super-admin@example.com',
            'password' => 'password123',
            'roles' => [FullAccessRoleProvisioner::SUPER_ADMIN_ROLE],
        ]);
    }

    public function test_super_admin_role_cannot_be_assigned_to_existing_admin(): void
    {
        app(FullAccessRoleProvisioner::class)->ensureSuperAdminRole();
        $admin = app(EloquentManagedAdminRepository::class)->createShelfSpotAdmin([
            'name' => 'Managed Admin',
            'email' => 'managed-admin@example.com',
            'password' => 'password123',
        ]);

        $this->expectException(AuthorizationException::class);

        app(EloquentManagedAdminRepository::class)->updateShelfSpotAdmin($admin, [
            'roles' => [FullAccessRoleProvisioner::SUPER_ADMIN_ROLE],
        ]);
    }

    public function test_company_owner_role_cannot_be_deleted(): void
    {
        $companyId = 10;
        $role = app(FullAccessRoleProvisioner::class)->ensureCompanyOwnerRole($companyId);

        $this->expectException(AuthorizationException::class);

        app(EloquentAccessControlRepository::class)->deleteRole(
            PermissionCatalog::COMPANY_PORTAL,
            $companyId,
            $role->id
        );
    }

    public function test_company_permissions_are_shared_across_company_roles(): void
    {
        $permissionName = CompanyPermissionEnum::VIEW_BRAND->value;
        $repository = app(EloquentAccessControlRepository::class);

        $firstCompanyRole = $repository->createRole(
            PermissionCatalog::COMPANY_PORTAL,
            10,
            ['name' => 'manager', 'permissions' => [$permissionName]]
        );

        $secondCompanyRole = $repository->createRole(
            PermissionCatalog::COMPANY_PORTAL,
            20,
            ['name' => 'manager', 'permissions' => [$permissionName]]
        );

        $this->assertFalse(Schema::hasColumn('permissions', 'company_id'));
        $this->assertDatabaseCount('permissions', count(CompanyPermissionEnum::cases()));
        $this->assertDatabaseHas('permissions', [
            'name' => $permissionName,
            'portal' => PermissionCatalog::COMPANY_PORTAL,
        ]);
        $this->assertSame(
            $firstCompanyRole->permissions->pluck('id')->all(),
            $secondCompanyRole->permissions->pluck('id')->all()
        );
    }

    public function test_roles_expose_only_the_permissions_that_are_not_assigned_as_available(): void
    {
        $assignedPermission = CompanyPermissionEnum::VIEW_BRAND->value;
        $repository = app(EloquentAccessControlRepository::class);

        $repository->createRole(
            PermissionCatalog::COMPANY_PORTAL,
            10,
            ['name' => 'catalog_manager', 'permissions' => [$assignedPermission]]
        );

        $role = $repository->roles(PermissionCatalog::COMPANY_PORTAL, 10)->first();

        $this->assertSame([$assignedPermission], $role->permissions->pluck('name')->all());
        $this->assertNotContains($assignedPermission, $role->availablePermissions->pluck('name')->all());
        $this->assertEqualsCanonicalizing(
            array_values(array_diff(PermissionCatalog::names(PermissionCatalog::COMPANY_PORTAL), [$assignedPermission])),
            $role->availablePermissions->pluck('name')->all()
        );
    }

    public function test_super_admin_user_cannot_be_deleted(): void
    {
        $admin = app(EloquentManagedAdminRepository::class)->createShelfSpotAdmin([
            'name' => 'Protected Super Admin',
            'email' => 'protected-super-admin@example.com',
            'password' => 'password123',
        ]);
        app(FullAccessRoleProvisioner::class)->assignSuperAdminRole($admin);

        $this->expectException(AuthorizationException::class);

        app(EloquentManagedAdminRepository::class)->deleteShelfSpotAdmin($admin);
    }

    public function test_company_owner_user_cannot_be_deleted(): void
    {
        $companyId = 10;
        $owner = app(EloquentManagedAdminRepository::class)->createCompanyAdmin($companyId, [
            'name' => 'Protected Owner',
            'email' => 'protected-owner@example.com',
            'password' => 'password123',
        ]);
        $owner->companyUser()->update(['is_owner' => true]);
        app(FullAccessRoleProvisioner::class)->assignCompanyOwnerRole($owner, $companyId);

        $this->expectException(AuthorizationException::class);

        app(EloquentManagedAdminRepository::class)->deleteCompanyAdmin($companyId, $owner);
    }

    public function test_company_owner_role_cannot_be_assigned_to_new_company_admin(): void
    {
        $companyId = 10;
        app(FullAccessRoleProvisioner::class)->ensureCompanyOwnerRole($companyId);

        $this->expectException(AuthorizationException::class);

        app(EloquentManagedAdminRepository::class)->createCompanyAdmin($companyId, [
            'name' => 'Second Owner',
            'email' => 'second-owner@example.com',
            'password' => 'password123',
            'roles' => [FullAccessRoleProvisioner::COMPANY_OWNER_ROLE],
        ]);
    }

    public function test_company_owner_role_cannot_be_assigned_to_existing_company_admin(): void
    {
        $companyId = 10;
        app(FullAccessRoleProvisioner::class)->ensureCompanyOwnerRole($companyId);
        $admin = app(EloquentManagedAdminRepository::class)->createCompanyAdmin($companyId, [
            'name' => 'Managed Company Admin',
            'email' => 'managed-company-admin@example.com',
            'password' => 'password123',
        ]);

        $this->expectException(AuthorizationException::class);

        app(EloquentManagedAdminRepository::class)->updateCompanyAdmin($companyId, $admin, [
            'roles' => [FullAccessRoleProvisioner::COMPANY_OWNER_ROLE],
        ]);
    }
}
