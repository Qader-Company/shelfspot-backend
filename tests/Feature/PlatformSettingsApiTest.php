<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckApiKey::class);
    }

    public function test_super_admin_can_fetch_and_update_platform_settings(): void
    {
        $this->authenticateSuperAdmin();

        $this->getJson('/api/v1/admin/platform-settings')
            ->assertOk()
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.phone', null);

        $this->patchJson('/api/v1/admin/platform-settings', [
            'email' => 'support@shelfspots.test',
            'phone' => '+201001234567',
            'address' => 'Cairo, Egypt',
            'description_ar' => 'دعم منصة شلف سبوتس.',
            'description_en' => 'ShelfSpots platform support.',
        ])
            ->assertOk()
            ->assertJsonPath('data.email', 'support@shelfspots.test')
            ->assertJsonPath('data.phone', '+201001234567')
            ->assertJsonPath('data.address', 'Cairo, Egypt')
            ->assertJsonPath('data.description_ar', 'دعم منصة شلف سبوتس.')
            ->assertJsonPath('data.description_en', 'ShelfSpots platform support.');

        $this->assertDatabaseHas('platform_settings', [
            'email' => 'support@shelfspots.test',
            'phone' => '+201001234567',
            'address' => 'Cairo, Egypt',
            'description_ar' => 'دعم منصة شلف سبوتس.',
            'description_en' => 'ShelfSpots platform support.',
        ]);
    }

    public function test_platform_settings_update_validates_email(): void
    {
        $this->authenticateSuperAdmin();

        $this->patchJson('/api/v1/admin/platform-settings', [
            'email' => 'not-an-email',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_admin_without_platform_settings_permissions_is_forbidden(): void
    {
        $user = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);
        Sanctum::actingAs($user, [PortalTypeEnum::ADMIN->value, 'access']);

        $this->getJson('/api/v1/admin/platform-settings')->assertForbidden();
        $this->patchJson('/api/v1/admin/platform-settings', ['phone' => '01000000000'])
            ->assertForbidden();
    }

    private function authenticateSuperAdmin(): void
    {
        $user = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);
        app(FullAccessRoleProvisioner::class)->assignSuperAdminRole($user);
        Sanctum::actingAs($user, [PortalTypeEnum::ADMIN->value, 'access']);
    }
}
