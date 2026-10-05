<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\Authentication\Application\DTOs\SocialUserData;
use App\Modules\V1\Authentication\Domain\Contracts\SocialProviderVerifierInterface;
use App\Modules\V1\Authentication\Infrastructure\Social\SocialProviderManager;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tests\TestCase;

class SocialLoginValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckApiKey::class);
        config()->set('social_auth.providers.google.allowed_portals', ['worker']);
        config()->set('social_auth.providers.apple.allowed_portals', ['worker']);
    }

    public static function providers(): array
    {
        return ['Google' => ['google'], 'Apple' => ['apple']];
    }

    #[DataProvider('providers')]
    public function test_social_login_routes_are_available_only_for_workers(string $provider): void
    {
        config()->set("social_auth.providers.{$provider}.allowed_portals", ['worker', 'admin', 'company']);
        $verifier = Mockery::mock(SocialProviderVerifierInterface::class);
        $verifier->shouldNotReceive('verify');
        $this->app->instance(SocialProviderManager::class, new SocialProviderManager([$provider => $verifier]));

        foreach (['admin', 'company'] as $portal) {
            $this->postJson("/api/v1/auth/{$portal}/social/{$provider}/login", ['token' => 'valid-token'])
                ->assertNotFound();
        }
    }

    #[DataProvider('providers')]
    public function test_a_nonempty_string_token_is_required(string $provider): void
    {
        $verifier = Mockery::mock(SocialProviderVerifierInterface::class);
        $verifier->shouldNotReceive('verify');
        $this->app->instance(SocialProviderManager::class, new SocialProviderManager([$provider => $verifier]));

        foreach ([[], ['token' => null], ['token' => ''], ['token' => 123]] as $payload) {
            $this->postJson("/api/v1/auth/worker/social/{$provider}/login", $payload)->assertUnprocessable();
        }
    }

    #[DataProvider('providers')]
    public function test_token_only_requests_reach_provider_verification(string $provider): void
    {
        $verifier = Mockery::mock(SocialProviderVerifierInterface::class);
        $verifier->shouldReceive('verify')->with('invalid-token')->once()
            ->andThrow(new UnauthorizedHttpException('', 'Invalid token'));
        $this->app->instance(SocialProviderManager::class, new SocialProviderManager([$provider => $verifier]));

        $this->postJson("/api/v1/auth/worker/social/{$provider}/login", ['token' => 'invalid-token'])->assertUnauthorized();
    }

    #[DataProvider('providers')]
    public function test_unverified_provider_emails_are_rejected(string $provider): void
    {
        $verifier = Mockery::mock(SocialProviderVerifierInterface::class);
        $verifier->shouldReceive('verify')->with('unverified-token')->once()->andReturn(new SocialUserData(
            providerUserId: 'provider-user-123',
            email: 'worker@example.com',
            emailVerified: false,
        ));
        $this->app->instance(SocialProviderManager::class, new SocialProviderManager([$provider => $verifier]));

        $this->postJson("/api/v1/auth/worker/social/{$provider}/login", ['token' => 'unverified-token'])->assertUnauthorized();
    }
}
