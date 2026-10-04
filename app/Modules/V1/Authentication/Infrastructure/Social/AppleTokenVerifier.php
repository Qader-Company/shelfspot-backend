<?php

namespace App\Modules\V1\Authentication\Infrastructure\Social;

use App\Modules\V1\Authentication\Application\DTOs\SocialUserData;
use App\Modules\V1\Authentication\Domain\Contracts\SocialProviderVerifierInterface;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;
use UnexpectedValueException;

class AppleTokenVerifier implements SocialProviderVerifierInterface
{
    private const PUBLIC_KEYS_CACHE_KEY = 'social-auth:apple:public-keys';

    public function __construct(
        private HttpFactory $http,
        private CacheRepository $cache,
    ) {}

    public function verify(string $token): SocialUserData
    {
        try {
            $clientIds = $this->configuredClientIds();
            $keyId = $this->tokenKeyId($token);
            $keys = $this->publicKeys();

            if (! isset($keys[$keyId])) {
                $this->cache->forget(self::PUBLIC_KEYS_CACHE_KEY);
                $keys = $this->publicKeys();
            }

            $claims = (array) JWT::decode($token, $keys);

            if (
                ($claims['iss'] ?? null) !== config('services.apple.issuer')
                || ! $this->hasAcceptedAudience($claims['aud'] ?? null, $clientIds)
                || empty($claims['sub'])
                || empty($claims['email'])
            ) {
                throw new UnexpectedValueException('Invalid Apple identity token claims.');
            }

            return new SocialUserData(
                providerUserId: (string) $claims['sub'],
                email: (string) $claims['email'],
                name: null,
                avatar: null,
                emailVerified: filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOL),
            );
        } catch (Throwable) {
            throw new UnauthorizedHttpException('', __('auth.credentials_mismatch'));
        }
    }

    /**
     * @return array<string, Key>
     */
    private function publicKeys(): array
    {
        return $this->cache->remember(
            self::PUBLIC_KEYS_CACHE_KEY,
            now()->addHours(6),
            function (): array {
                $keySet = $this->http
                    ->connectTimeout(3)
                    ->timeout(5)
                    ->get(config('services.apple.keys_url'))
                    ->throw()
                    ->json();

                if (! is_array($keySet)) {
                    throw new RuntimeException('Apple returned an invalid public key set.');
                }

                return JWK::parseKeySet($keySet, 'RS256');
            }
        );
    }

    private function tokenKeyId(string $token): string
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            throw new UnexpectedValueException('Malformed Apple identity token.');
        }

        $header = JWT::jsonDecode(JWT::urlsafeB64Decode($segments[0]));

        if (! isset($header->kid) || ! is_string($header->kid) || $header->kid === '') {
            throw new UnexpectedValueException('Apple identity token has no key ID.');
        }

        return $header->kid;
    }

    /**
     * @return list<string>
     */
    private function configuredClientIds(): array
    {
        $clientIds = config('services.apple.client_ids', []);

        if (! is_array($clientIds) || $clientIds === []) {
            throw new RuntimeException('Apple Sign In is not configured.');
        }

        $clientIds = array_values(array_filter(
            $clientIds,
            fn ($clientId) => is_string($clientId) && $clientId !== ''
        ));

        if ($clientIds === []) {
            throw new RuntimeException('Apple Sign In is not configured.');
        }

        return $clientIds;
    }

    /**
     * @param  list<string>  $clientIds
     */
    private function hasAcceptedAudience(mixed $audience, array $clientIds): bool
    {
        $audiences = is_array($audience) ? $audience : [$audience];

        return array_intersect($clientIds, $audiences) !== [];
    }
}
