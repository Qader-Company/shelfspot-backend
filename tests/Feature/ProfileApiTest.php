<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\Admins\Domain\Models\ShelfSpotAdmin;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckApiKey::class);
    }

    public function test_admin_can_retrieve_and_update_its_profile(): void
    {
        $user = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);
        ShelfSpotAdmin::query()->create(['user_id' => $user->id, 'is_active' => true]);
        app(FullAccessRoleProvisioner::class)->assignSuperAdminRole($user);
        $expectedPermissions = PermissionCatalog::names(PermissionCatalog::ADMIN_PORTAL);
        sort($expectedPermissions);

        $this->postJson('/api/v1/auth/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('data.user.roles', ['super_admin'])
            ->assertJsonPath('data.user.permissions', $expectedPermissions);

        Sanctum::actingAs($user, ['admin', 'access']);

        $this->getJson('/api/v1/admin/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.type', 'admin')
            ->assertJsonPath('data.roles', ['super_admin'])
            ->assertJsonPath('data.permissions', $expectedPermissions);

        $this->patchJson('/api/v1/admin/profile', ['name' => 'Updated Admin'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Admin');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Admin']);
    }

    public function test_company_user_can_retrieve_and_update_its_profile(): void
    {
        $company = $this->company();
        $user = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);
        CompanyUser::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'is_active' => true,
            'is_owner' => true,
        ]);
        app(FullAccessRoleProvisioner::class)->assignCompanyOwnerRole($user, $company->id);
        $expectedPermissions = PermissionCatalog::names(PermissionCatalog::COMPANY_PORTAL);
        sort($expectedPermissions);
        Sanctum::actingAs($user, ['company', 'access']);

        $this->getJson('/api/v1/company/profile', ['X-Company-id' => $company->id])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.type', 'company')
            ->assertJsonPath('data.company_id', $company->id)
            ->assertJsonPath('data.company_name', $company->name)
            ->assertJsonPath('data.roles', ['owner'])
            ->assertJsonPath('data.permissions', $expectedPermissions);

        $this->patchJson('/api/v1/company/profile', ['name' => 'Updated Company User'], [
            'X-Company-id' => $company->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Company User');
    }

    public function test_login_returns_the_company_users_permissions(): void
    {
        $company = $this->company();
        $user = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);
        CompanyUser::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'is_active' => true,
            'is_owner' => true,
        ]);
        app(FullAccessRoleProvisioner::class)->assignCompanyOwnerRole($user, $company->id);
        $expectedPermissions = PermissionCatalog::names(PermissionCatalog::COMPANY_PORTAL);
        sort($expectedPermissions);

        $this->postJson('/api/v1/auth/company/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.roles', ['owner'])
            ->assertJsonPath('data.user.permissions', $expectedPermissions);
    }

    public function test_company_owner_can_update_its_company_when_it_has_the_edit_permission(): void
    {
        $company = $this->company();
        $user = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);
        CompanyUser::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'is_active' => true,
            'is_owner' => true,
        ]);
        app(FullAccessRoleProvisioner::class)->assignCompanyOwnerRole($user, $company->id);
        Sanctum::actingAs($user, ['company', 'access']);

        $this->patchJson('/api/v1/company/profile/company', [
            'name' => 'Updated Company',
            'phone' => '01112345678',
        ], ['X-Company-id' => $company->id])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Company');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Updated Company',
            'phone' => '01112345678',
            'is_active' => true,
        ]);

        $this->patchJson('/api/v1/company/profile/company', ['is_active' => false], [
            'X-Company-id' => $company->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');
    }

    public function test_company_user_cannot_update_its_company_without_the_edit_permission(): void
    {
        $company = $this->company();
        $user = User::factory()->create(['type' => PortalTypeEnum::COMPANY]);
        CompanyUser::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);
        Sanctum::actingAs($user, ['company', 'access']);

        $this->patchJson('/api/v1/company/profile/company', [
            'name' => 'Unauthorized Update',
        ], ['X-Company-id' => $company->id])
            ->assertForbidden();

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Profile Company',
        ]);
    }

    public function test_worker_profile_updates_its_model_fields_and_rejects_other_portal_fields(): void
    {
        $user = User::factory()->create(['type' => PortalTypeEnum::WORKER]);
        $worker = Worker::query()->create([
            'user_id' => $user->id,
            'phone' => '01000000000',
            'is_active' => true,
        ]);
        Sanctum::actingAs($user, ['worker', 'access']);

        $this->getJson('/api/v1/worker/account/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.type', 'worker');

        $this->patchJson('/api/v1/worker/account/profile', [
            'name' => 'Updated Worker',
            'phone' => '01100000000',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated Worker')
            ->assertJsonPath('data.phone', '01100000000');

        $this->patchJson('/api/v1/worker/account/profile', ['is_active' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');

        $this->assertDatabaseHas('workers', ['id' => $worker->id, 'is_active' => true]);
    }

    public function test_worker_can_update_its_location_with_a_location_name(): void
    {
        $user = User::factory()->create(['type' => PortalTypeEnum::WORKER]);
        $worker = Worker::query()->create([
            'user_id' => $user->id,
            'phone' => '01000000001',
            'is_active' => true,
        ]);
        Sanctum::actingAs($user, ['worker', 'access']);

        $this->patchJson('/api/v1/worker/account/location', [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'location_name' => 'Tahrir Square, Cairo',
        ])->assertOk()
            ->assertJsonPath('data.last_location.latitude', '30.0444000')
            ->assertJsonPath('data.last_location.longitude', '31.2357000')
            ->assertJsonPath('data.last_location.name', 'Tahrir Square, Cairo');

        $this->assertDatabaseHas('workers', [
            'id' => $worker->id,
            'last_location_name' => 'Tahrir Square, Cairo',
        ]);
    }

    public function test_worker_can_add_and_replace_an_optional_profile_image(): void
    {
        $user = User::factory()->create(['type' => PortalTypeEnum::WORKER]);
        $worker = Worker::query()->create([
            'user_id' => $user->id,
            'phone' => '01000000002',
            'is_active' => true,
        ]);
        Sanctum::actingAs($user, ['worker', 'access']);

        $this->getJson('/api/v1/worker/account/profile')
            ->assertOk()
            ->assertJsonPath('data.image', null);

        $this->patch('/api/v1/worker/account/profile', [
            'image' => UploadedFile::fake()->image('worker.png'),
        ])->assertOk()
            ->assertJsonPath('data.image', fn ($image) => is_string($image) && $image !== '');

        $this->assertSame(1, $worker->fresh()->getMedia('image')->count());

        $this->patch('/api/v1/worker/account/profile', [
            'image' => UploadedFile::fake()->image('replacement.jpg'),
        ])->assertOk();

        $this->assertSame(1, $worker->fresh()->getMedia('image')->count());
    }

    private function company(): Company
    {
        return Company::query()->create([
            'name' => 'Profile Company',
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('010########'),
            'cr_number' => fake()->unique()->numerify('CR-####'),
            'industry' => CompanyIndustryEnum::INDUSTRY_ONE,
            'is_active' => true,
        ]);
    }
}
