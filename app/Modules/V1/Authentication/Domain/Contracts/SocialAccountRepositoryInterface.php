<?php

namespace App\Modules\V1\Authentication\Domain\Contracts;

use App\Modules\V1\Authentication\Application\DTOs\SocialUserData;
use App\Modules\V1\Authentication\Domain\Models\SocialAccount;
use App\Modules\V1\Authentication\Domain\ValueObjects\SocialProviderEnum;
use App\Modules\V1\Users\Domain\Models\User;

interface SocialAccountRepositoryInterface
{
    public function find(SocialProviderEnum $provider, string $providerUserId): ?SocialAccount;

    public function createForUser(User $user, SocialProviderEnum $provider, SocialUserData $socialUser): SocialAccount;
}
