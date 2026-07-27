<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Application\Jobs\SendWorkerCredentialsEmailJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminWorkerCredentialsEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_worker_queues_the_credentials_email(): void
    {
        $this->withoutMiddleware([CheckApiKey::class]);
        Queue::fake();

        $admin = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);
        app(FullAccessRoleProvisioner::class)->assignSuperAdminRole($admin);
        Sanctum::actingAs($admin, [PortalTypeEnum::ADMIN->value, 'access']);

        $this->postJson('/api/v1/admin/workers', [
            'name' => 'Jane Worker',
            'email' => 'jane@example.test',
            'phone' => '01123456789',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ])->assertCreated();

        Queue::assertPushed(SendWorkerCredentialsEmailJob::class, 1);
    }

    public function test_admin_can_add_and_replace_a_worker_image(): void
    {
        $this->withoutMiddleware([CheckApiKey::class]);
        Queue::fake();

        $admin = User::factory()->create(['type' => PortalTypeEnum::ADMIN]);
        app(FullAccessRoleProvisioner::class)->assignSuperAdminRole($admin);
        Sanctum::actingAs($admin, [PortalTypeEnum::ADMIN->value, 'access']);

        $this->post('/api/v1/admin/workers', [
            'name' => 'Jane Worker',
            'email' => 'jane.image@example.test',
            'phone' => '01123456780',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'image' => UploadedFile::fake()->image('worker.png'),
        ])->assertCreated()
            ->assertJsonPath('data.image', fn ($image) => is_string($image) && $image !== '');

        $worker = \App\Modules\V1\Workers\Domain\Models\Worker::query()->firstOrFail();

        $this->patch('/api/v1/admin/workers/'.$worker->id, [
            'image' => UploadedFile::fake()->image('replacement.jpg'),
        ])->assertOk();

        $this->assertSame(1, $worker->fresh()->getMedia('image')->count());
    }
}
