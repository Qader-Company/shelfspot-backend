<?php

namespace App\Modules\V1\Authentication\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Authentication\Application\DTOs\SocialUserData;
use App\Modules\V1\Authentication\Domain\Contracts\SocialAccountRepositoryInterface;
use App\Modules\V1\Authentication\Domain\Models\SocialAccount;
use App\Modules\V1\Authentication\Domain\ValueObjects\SocialProviderEnum;
use App\Modules\V1\Users\Domain\Models\User;

class EloquentSocialAccountRepository implements SocialAccountRepositoryInterface
{
    public function find(SocialProviderEnum $provider, string $providerUserId): ?SocialAccount
    {
        return SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();
    }

    public function createForUser(User $user, SocialProviderEnum $provider, SocialUserData $socialUser): SocialAccount
    {
        return SocialAccount::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_user_id' => $socialUser->providerUserId,
            'email' => $socialUser->email,
            'name' => $socialUser->name,
            'avatar' => $socialUser->avatar,
        ]);
    }
}
