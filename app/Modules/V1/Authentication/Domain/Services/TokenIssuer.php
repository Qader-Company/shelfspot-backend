<?php


namespace App\Modules\V1\Authentication\Domain\Services;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Carbon\Carbon;


class TokenIssuer
{
    public function create(User $user, PortalTypeEnum $portal, TokenTypeEnum $tokenType): array
    {
        return [
            'token' => $user->createToken(
                name: $portal->value,
                abilities: [
                    $tokenType->value,
                ],
                expiresAt: Carbon::now()->addMinutes(config('sanctum.' . $tokenType->value . '_token_TTL'))
            )->plainTextToken,
            'ttl' => config('sanctum.' . $tokenType->value . '_token_TTL'),
        ];
    }

    public function refreshToken(User $user, PortalTypeEnum $portal): array
    {
        $user->currentAccessToken()?->delete();

        return [
            'access_token' => $this->create(
                $user,
                $portal,
                TokenTypeEnum::ACCESS_TOKEN
            ),
            'refresh_token' => $this->create(
                $user,
                $portal,
                TokenTypeEnum::REFRESH_TOKEN
            ),
        ];
    }
}
