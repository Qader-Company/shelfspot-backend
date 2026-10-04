<?php

namespace Tests\Unit;

use App\Modules\V1\Authentication\Infrastructure\Social\AppleTokenVerifier;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tests\TestCase;

class AppleTokenVerifierTest extends TestCase
{
    private const KEY_ID = 'apple-test-key';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('social-auth:apple:public-keys');
        config()->set('services.apple.client_ids', ['com.example.worker']);
        config()->set('services.apple.issuer', 'https://appleid.apple.com');
        config()->set('services.apple.keys_url', 'https://appleid.apple.com/auth/keys');
    }

    public function test_it_verifies_an_apple_identity_token(): void
    {
        [$privateKey, $jwk] = $this->rsaKeyPair();
        Http::fake([
            'https://appleid.apple.com/auth/keys' => Http::response(['keys' => [$jwk]]),
        ]);

        $token = JWT::encode([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'com.example.worker',
            'sub' => 'apple-user-123',
            'email' => 'worker@privaterelay.appleid.com',
            'email_verified' => 'true',
            'iat' => time() - 10,
            'exp' => time() + 300,
        ], $privateKey, 'RS256', self::KEY_ID);

        $socialUser = app(AppleTokenVerifier::class)->verify($token);

        $this->assertSame('apple-user-123', $socialUser->providerUserId);
        $this->assertSame('worker@privaterelay.appleid.com', $socialUser->email);
        $this->assertTrue($socialUser->emailVerified);
        $this->assertNull($socialUser->name);
        $this->assertNull($socialUser->avatar);
        Http::assertSentCount(1);
    }

    public function test_it_rejects_a_token_for_another_client(): void
    {
        [$privateKey, $jwk] = $this->rsaKeyPair();
        Http::fake([
            'https://appleid.apple.com/auth/keys' => Http::response(['keys' => [$jwk]]),
        ]);

        $token = JWT::encode([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'com.example.another-app',
            'sub' => 'apple-user-123',
            'email' => 'worker@example.com',
            'email_verified' => true,
            'iat' => time() - 10,
            'exp' => time() + 300,
        ], $privateKey, 'RS256', self::KEY_ID);

        $this->expectException(UnauthorizedHttpException::class);

        app(AppleTokenVerifier::class)->verify($token);
    }

    public function test_it_rejects_an_expired_token(): void
    {
        [$privateKey, $jwk] = $this->rsaKeyPair();
        Http::fake([
            'https://appleid.apple.com/auth/keys' => Http::response(['keys' => [$jwk]]),
        ]);

        $token = JWT::encode([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'com.example.worker',
            'sub' => 'apple-user-123',
            'email' => 'worker@example.com',
            'email_verified' => true,
            'iat' => time() - 600,
            'exp' => time() - 300,
        ], $privateKey, 'RS256', self::KEY_ID);

        $this->expectException(UnauthorizedHttpException::class);

        app(AppleTokenVerifier::class)->verify($token);
    }

    /**
     * @return array{0: \OpenSSLAsymmetricKey, 1: array<string, string>}
     */
    private function rsaKeyPair(): array
    {
        $privateKey = openssl_pkey_new([
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $details = openssl_pkey_get_details($privateKey);

        return [
            $privateKey,
            [
                'kty' => 'RSA',
                'kid' => self::KEY_ID,
                'use' => 'sig',
                'alg' => 'RS256',
                'n' => JWT::urlsafeB64Encode($details['rsa']['n']),
                'e' => JWT::urlsafeB64Encode($details['rsa']['e']),
            ],
        ];
    }
}
