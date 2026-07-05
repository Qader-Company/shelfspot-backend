<?php

namespace App\Modules\V1\Authentication\Infrastructure\Social;

use App\Modules\V1\Authentication\Application\DTOs\SocialUserData;
use App\Modules\V1\Authentication\Domain\Contracts\SocialProviderVerifierInterface;
use Google\Client as GoogleClient;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class GoogleTokenVerifier implements SocialProviderVerifierInterface
{
    public function __construct(private GoogleClient $client)
    {
    }

    public function verify(string $token): SocialUserData
    {
        try {
            $payload = $this->client->verifyIdToken($token);
        }catch (\Exception $e){
            throw new UnauthorizedHttpException('', __('auth.credentials_mismatch'));
        }
        if (! $payload || empty($payload['sub']) || empty($payload['email'])) {
            throw new UnauthorizedHttpException('', __('auth.credentials_mismatch'));
        }
        return new SocialUserData(
            providerUserId: (string) $payload['sub'],
            email: (string) $payload['email'],
            name: $payload['name'] ?? null,
            avatar: $payload['picture'] ?? null,
            emailVerified: filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOL),
        );
    }
}
