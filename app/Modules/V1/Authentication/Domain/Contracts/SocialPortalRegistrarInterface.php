<?php

namespace App\Modules\V1\Authentication\Domain\Contracts;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

interface SocialPortalRegistrarInterface
{
    public function portal(): PortalTypeEnum;

    public function register(string $email, ?string $name, array $attributes): User;

    public function ensureProfile(User $user, array $attributes): User;
}
