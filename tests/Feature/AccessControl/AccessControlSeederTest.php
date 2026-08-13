<?php

namespace Tests\Feature\AccessControl;

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Models\Permission;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Database\Seeders\AccessControlPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_rerunning_seeder_restores_catalog_and_all_full_access_permissions(): void
    {
        $provisioner = app(FullAccessRoleProvisioner::class);

        $superAdmin = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);
        $provisioner->assignSuperAdminRole($superAdmin);

        $company = Company::query()->create([
            'name' => 'Seeder Company',
            'cr_number' => 'CR-SEEDER',
            'email' => 'seeder-company@example.com',
            'phone' => '500000001',
            'industry' => 'retail',
        ]);
        $owner = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);
        CompanyUser::query()->create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'is_owner' => true,
            'is_active' => true,
        ]);

        $adminRole = $superAdmin->roles()->firstOrFail();
        $adminRole->revokePermissionTo(AdminPermissionEnum::DELETE_COMPANY->value);

        $this->seed(AccessControlPermissionSeeder::class);
        $this->seed(AccessControlPermissionSeeder::class);

        $ownerRole = Role::query()
            ->where('portal', PermissionCatalog::COMPANY_PORTAL)
            ->where('company_id', $company->id)
            ->where('name', FullAccessRoleProvisioner::COMPANY_OWNER_ROLE)
            ->firstOrFail();

        $this->assertEqualsCanonicalizing(
            array_column(AdminPermissionEnum::cases(), 'value'),
            Permission::query()->where('portal', PermissionCatalog::ADMIN_PORTAL)->pluck('name')->all(),
        );
        $this->assertEqualsCanonicalizing(
            array_column(CompanyPermissionEnum::cases(), 'value'),
            Permission::query()->where('portal', PermissionCatalog::COMPANY_PORTAL)->pluck('name')->all(),
        );
        $this->assertEqualsCanonicalizing(
            array_column(AdminPermissionEnum::cases(), 'value'),
            $adminRole->fresh()->permissions()->pluck('name')->all(),
        );
        $this->assertEqualsCanonicalizing(
            array_column(CompanyPermissionEnum::cases(), 'value'),
            $ownerRole->permissions()->pluck('name')->all(),
        );
        $this->assertTrue($owner->roles()->whereKey($ownerRole->id)->exists());
    }
}
