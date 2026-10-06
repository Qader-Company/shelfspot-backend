<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\Authentication\Application\DTOs\SocialUserData;
use App\Modules\V1\Authentication\Domain\Contracts\SocialProviderVerifierInterface;
use App\Modules\V1\Authentication\Infrastructure\Social\SocialProviderManager;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tests\TestCase;

class WorkerSocialLoginDeviceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckApiKey::class);
        config([
            'social_auth.providers.google.allowed_portals' => ['worker'],
            'social_auth.providers.apple.allowed_portals' => ['worker'],
        ]);
    }

    public static function providers(): array
    {
        return [
            'Google' => ['google'],
            'Apple' => ['apple'],
        ];
    }

    #[DataProvider('providers')]
    public function test_successful_login_registers_the_worker_device(string $provider): void
    {
        $deviceType = $provider === 'apple' ? 'ios' : 'android';
        $this->fakeProvider($provider);

        $this->postJson("/api/v1/auth/worker/social/$provider/login", [
            'token' => 'valid-provider-token',
            'device_token' => 'test-fcm-token',
            'device_type' => $deviceType,
            'device_name' => 'Test phone',
        ])->assertOk()->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);

        $user = User::query()->where('email', 'worker@example.com')->sole();
        $this->assertSame(PortalTypeEnum::WORKER, $user->type);
        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'test-fcm-token',
            'device_type' => $deviceType,
            'device_name' => 'Test phone',
        ]);
        $this->assertNotNull($user->deviceTokens()->sole()->last_used_at);
    }

    public static function optionalDevices(): array
    {
        $cases = [];

        foreach (['google', 'apple'] as $provider) {
            foreach (['omitted' => [], 'empty' => [
                'device_token' => '', 'device_type' => '', 'device_name' => '',
            ], 'null' => [
                'device_token' => null, 'device_type' => null, 'device_name' => null,
            ]] as $name => $attributes) {
                $cases["$provider $name"] = [$provider, $attributes];
            }
        }

        return $cases;
    }

    #[DataProvider('optionalDevices')]
    public function test_login_succeeds_without_a_device_token(string $provider, array $attributes): void
    {
        $this->fakeProvider($provider);

        $this->postJson("/api/v1/auth/worker/social/$provider/login", [
            'token' => 'valid-provider-token',
            ...$attributes,
        ])->assertOk();

        $this->assertDatabaseCount('device_tokens', 0);
    }

    #[DataProvider('providers')]
    public function test_existing_worker_can_register_a_token_without_device_metadata(string $provider): void
    {
        $user = User::factory()->create(['type' => PortalTypeEnum::WORKER]);
        $user->worker()->create(['phone' => '01234567890', 'is_active' => true]);
        $previousUser = User::factory()->create(['type' => PortalTypeEnum::WORKER]);
        $previousUser->deviceTokens()->create(['token' => 'shared-device-token']);
        $user->deviceTokens()->create(['token' => 'other-device-token']);
        $this->fakeProvider($provider, $user->email);

        $this->postJson("/api/v1/auth/worker/social/$provider/login", [
            'token' => 'valid-provider-token',
            'device_token' => 'shared-device-token',
        ])->assertOk();

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'shared-device-token',
            'device_type' => null,
            'device_name' => null,
        ]);
        $this->assertDatabaseCount('device_tokens', 2);
        $this->assertSame(2, $user->deviceTokens()->count());
    }

    public static function invalidDevices(): array
    {
        $cases = [];

        foreach (['google', 'apple'] as $provider) {
            foreach ([
                'invalid token type' => [['device_token' => []], 'device_token'],
                'long token' => [['device_token' => str_repeat('a', 513)], 'device_token'],
                'invalid device type' => [['device_token' => 'fcm-token', 'device_type' => 'web'], 'device_type'],
                'long device name' => [['device_token' => 'fcm-token', 'device_name' => str_repeat('a', 256)], 'device_name'],
                'type without token' => [['device_type' => 'android'], 'device_type'],
                'name without token' => [['device_name' => 'Test phone'], 'device_name'],
            ] as $name => [$attributes, $field]) {
                $cases["$provider $name"] = [$provider, $attributes, $field];
            }
        }

        return $cases;
    }

    #[DataProvider('invalidDevices')]
    public function test_invalid_device_fields_are_rejected(string $provider, array $attributes, string $field): void
    {
        $verifier = Mockery::mock(SocialProviderVerifierInterface::class);
        $verifier->shouldNotReceive('verify');
        $this->app->instance(SocialProviderManager::class, new SocialProviderManager([$provider => $verifier]));

        $this->postJson("/api/v1/auth/worker/social/$provider/login", [
            'token' => 'valid-provider-token',
            ...$attributes,
        ])->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('device_tokens', 0);
    }

    #[DataProvider('providers')]
    public function test_invalid_provider_token_does_not_register_a_device(string $provider): void
    {
        $verifier = Mockery::mock(SocialProviderVerifierInterface::class);
        $verifier->shouldReceive('verify')->once()->with('invalid-provider-token')
            ->andThrow(new UnauthorizedHttpException('', 'Invalid provider token.'));
        $this->app->instance(SocialProviderManager::class, new SocialProviderManager([$provider => $verifier]));

        $this->postJson("/api/v1/auth/worker/social/$provider/login", [
            'token' => 'invalid-provider-token',
            'device_token' => 'test-fcm-token',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('device_tokens', 0);
    }

    #[DataProvider('providers')]
    public function test_inactive_worker_does_not_register_a_device(string $provider): void
    {
        $user = User::factory()->create(['type' => PortalTypeEnum::WORKER]);
        $user->worker()->create(['phone' => '01234567890', 'is_active' => false]);
        $this->fakeProvider($provider, $user->email);

        $this->postJson("/api/v1/auth/worker/social/$provider/login", [
            'token' => 'valid-provider-token',
            'device_token' => 'test-fcm-token',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('device_tokens', 0);
    }

    private function fakeProvider(string $provider, string $email = 'worker@example.com'): void
    {
        $verifier = Mockery::mock(SocialProviderVerifierInterface::class);
        $verifier->shouldReceive('verify')->once()->with('valid-provider-token')->andReturn(
            new SocialUserData('provider-user-id', $email, 'Test Worker', emailVerified: true),
        );
        $this->app->instance(SocialProviderManager::class, new SocialProviderManager([$provider => $verifier]));
    }
}
