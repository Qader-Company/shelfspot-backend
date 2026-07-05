<?php

namespace App\Modules\V1\Authentication\Domain\Contracts;

use App\Modules\V1\Authentication\Application\DTOs\SocialUserData;

interface SocialProviderVerifierInterface
{
    public function verify(string $token): SocialUserData;
}
