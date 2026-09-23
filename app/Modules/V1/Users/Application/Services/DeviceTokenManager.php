<?php

namespace App\Modules\V1\Users\Application\Services;

use App\Modules\V1\Users\Domain\Models\DeviceToken;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

class DeviceTokenManager
{
    public function registerWorkerDevice(User $user, array $attributes): ?DeviceToken
    {
        $token = $attributes['device_token'] ?? null;

        if ($user->type !== PortalTypeEnum::WORKER || ! is_string($token) || $token === '') {
            return null;
        }

        return DeviceToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'device_type' => $attributes['device_type'] ?? null,
                'device_name' => $attributes['device_name'] ?? null,
                'last_used_at' => now(),
            ],
        );
    }

    public function removeDevice(User $user, ?string $token): void
    {
        if (! is_string($token) || $token === '') {
            return;
        }

        $user->deviceTokens()->where('token', $token)->delete();
    }
}
