<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\Admins\Domain\Models\ShelfSpotAdmin;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        Sanctum::actingAs($user, ['admin', 'access']);

        $this->getJson('/api/v1/admin/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.type', 'admin');

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
        Sanctum::actingAs($user, ['company', 'access']);

        $this->getJson('/api/v1/company/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.type', 'company')
            ->assertJsonPath('data.company_id', $company->id);

        $this->patchJson('/api/v1/company/profile', ['name' => 'Updated Company User'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Company User');
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

        $this->patchJson('/api/v1/worker/profile', [
            'name' => 'Updated Worker',
            'phone' => '01100000000',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated Worker')
            ->assertJsonPath('data.phone', '01100000000');

        $this->patchJson('/api/v1/worker/profile', ['is_active' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');

        $this->assertDatabaseHas('workers', ['id' => $worker->id, 'is_active' => true]);
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
