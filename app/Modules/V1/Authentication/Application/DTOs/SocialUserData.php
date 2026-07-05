<?php

namespace App\Modules\V1\Authentication\Application\DTOs;

class SocialUserData
{
    public function __construct(
        public readonly string $providerUserId,
        public readonly string $email,
        public readonly ?string $name = null,
        public readonly ?string $avatar = null,
        public readonly bool $emailVerified = false,
    ) {
    }
}
