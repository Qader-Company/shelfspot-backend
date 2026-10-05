<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\Authentication\Application\DTOs\SocialUserData;
use App\Modules\V1\Authentication\Domain\Contracts\SocialProviderVerifierInterface;
use App\Modules\V1\Authentication\Infrastructure\Social\SocialProviderManager;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckApiKey::class);
        config()->set('social_auth.providers.google.allowed_portals', ['worker']);
        config()->set('social_auth.providers.apple.allowed_portals', ['worker']);
    }

    public static function providers(): array
    {
        return [
            'Google' => ['google', 'Google Worker'],
            'Apple' => ['apple', null],
        ];
    }

    #[DataProvider('providers')]
    public function test_token_only_login_creates_accounts_and_reuses_the_linked_account(string $provider, ?string $name): void
    {
        $this->fakeProvider($provider, $name);

        $response = $this->postJson("/api/v1/auth/worker/social/{$provider}/login", ['token' => 'valid-token']);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'worker@example.com')
            ->assertJsonPath('data.user.name', $name ?? 'worker@example.com')
            ->assertJsonPath('data.user.phone', null)
            ->assertJsonPath('data.user.is_active', true)
            ->assertJsonStructure(['data' => ['access_token' => ['token'], 'refresh_token' => ['token']]]);

        $this->assertNotNull(User::firstOrFail()->email_verified_at);
        $this->assertDatabaseHas('social_accounts', ['provider' => $provider, 'provider_user_id' => 'provider-user-123']);

        $this->postJson("/api/v1/auth/worker/social/{$provider}/login", ['token' => 'valid-token'])->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('workers', 1);
        $this->assertDatabaseCount('social_accounts', 1);

        $this->fakeProvider($provider, $name, 'another-worker@example.com', 'provider-user-456');
        $this->postJson("/api/v1/auth/worker/social/{$provider}/login", ['token' => 'valid-token'])->assertOk();

        $this->assertDatabaseCount('workers', 2);
        $this->assertSame(2, Worker::whereNull('phone')->count());
    }

    #[DataProvider('providers')]
    public function test_token_only_login_links_an_existing_worker_without_changing_the_profile(string $provider, ?string $name): void
    {
        $user = User::factory()->create(['email' => 'worker@example.com', 'type' => PortalTypeEnum::WORKER]);
        $worker = Worker::create(['user_id' => $user->id, 'phone' => '+201000000000', 'is_active' => true]);
        $this->fakeProvider($provider, $name);

        $this->postJson("/api/v1/auth/worker/social/{$provider}/login", ['token' => 'valid-token'])
            ->assertOk()
            ->assertJsonPath('data.user.phone', $worker->phone);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('workers', 1);
        $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id, 'provider' => $provider]);
    }

    #[DataProvider('providers')]
    public function test_token_only_login_creates_a_missing_worker_profile(string $provider, ?string $name): void
    {
        $user = User::factory()->create(['email' => 'worker@example.com', 'type' => PortalTypeEnum::WORKER]);
        $this->fakeProvider($provider, $name);

        $this->postJson("/api/v1/auth/worker/social/{$provider}/login", ['token' => 'valid-token'])->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('workers', ['user_id' => $user->id, 'phone' => null, 'is_active' => true]);
    }

    #[DataProvider('providers')]
    public function test_only_the_token_is_used_from_the_request(string $provider, ?string $name): void
    {
        $this->fakeProvider($provider, $name);

        $this->postJson("/api/v1/auth/worker/social/{$provider}/login", [
            'token' => 'valid-token',
            'name' => 'Untrusted Name',
            'phone' => '+201000000000',
            'latitude' => 30,
            'longitude' => 31,
            'device_token' => 'fcm-token',
            'device_type' => 'ios',
        ])->assertOk()->assertJsonPath('data.user.name', $name ?? 'worker@example.com');

        $this->assertDatabaseHas('workers', ['phone' => null, 'last_latitude' => null, 'last_longitude' => null]);
        $this->assertDatabaseCount('device_tokens', 0);
    }

    private function fakeProvider(string $provider, ?string $name, string $email = 'worker@example.com', string $providerUserId = 'provider-user-123'): void
    {
        $verifier = Mockery::mock(SocialProviderVerifierInterface::class);
        $verifier->shouldReceive('verify')->with('valid-token')->andReturn(new SocialUserData(
            providerUserId: $providerUserId,
            email: $email,
            name: $name,
            avatar: null,
            emailVerified: true,
        ));

        $this->app->instance(SocialProviderManager::class, new SocialProviderManager([$provider => $verifier]));
    }
}
