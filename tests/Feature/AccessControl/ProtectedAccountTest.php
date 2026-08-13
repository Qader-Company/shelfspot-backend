<?php

namespace Tests\Feature\AccessControl;

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\AccessControl\Domain\Repositories\ManagedAdminRepositoryInterface;
use App\Modules\V1\Admins\Domain\Models\ShelfSpotAdmin;
use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtectedAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_cannot_be_modified_through_access_management(): void
    {
        $user = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);
        ShelfSpotAdmin::query()->create(['user_id' => $user->id, 'is_active' => true]);
        app(FullAccessRoleProvisioner::class)->assignSuperAdminRole($user);

        $this->expectException(AuthorizationException::class);

        app(ManagedAdminRepositoryInterface::class)->updateShelfSpotAdmin($user, [
            'name' => 'Changed',
            'email' => 'changed-admin@example.com',
            'is_active' => false,
            'roles' => [],
        ]);
    }

    public function test_company_owner_cannot_be_modified_through_access_management(): void
    {
        $company = $this->createCompany();
        $user = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);
        CompanyUser::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'is_owner' => true,
            'is_active' => true,
        ]);
        app(FullAccessRoleProvisioner::class)->assignCompanyOwnerRole($user, $company->id);

        $this->expectException(AuthorizationException::class);

        app(ManagedAdminRepositoryInterface::class)->updateCompanyAdmin($company->id, $user, [
            'name' => 'Changed',
            'email' => 'changed-owner@example.com',
            'is_active' => false,
            'roles' => [],
        ]);
    }

    private function createCompany(): Company
    {
        return Company::query()->create([
            'name' => 'Protected Company',
            'cr_number' => 'CR-PROTECTED',
            'email' => 'protected-company@example.com',
            'phone' => '500000002',
            'industry' => 'retail',
        ]);
    }
}
