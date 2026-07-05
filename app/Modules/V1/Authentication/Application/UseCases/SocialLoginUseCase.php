<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Authentication\Domain\Contracts\SocialAccountRepositoryInterface;
use App\Modules\V1\Authentication\Domain\Services\TokenIssuer;
use App\Modules\V1\Authentication\Domain\ValueObjects\SocialProviderEnum;
use App\Modules\V1\Authentication\Infrastructure\Social\SocialProviderManager;
use App\Modules\V1\Users\Application\Services\UserActivationChecker;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class SocialLoginUseCase
{
    public function __construct(
        private SocialProviderManager $providers,
        private SocialAccountRepositoryInterface $socialAccounts,
        private UserRepositoryInterface $users,
        private TokenIssuer $tokenIssuer,
    ) {
    }

    public function execute(SocialProviderEnum $provider, PortalTypeEnum $portal, string $token): array
    {
        $socialUser = $this->providers->driver($provider)->verify($token);

        if (! $socialUser->emailVerified) {
            throw new UnauthorizedHttpException('', __('auth.credentials_mismatch'));
        }

        $user = DB::transaction(function () use ($provider, $portal, $socialUser) {
            $socialAccount = $this->socialAccounts->find($provider, $socialUser->providerUserId);

            if ($socialAccount) {
                return $socialAccount->user;
            }

            $user = $this->users->findWhere([
                'email' => $socialUser->email,
                'type' => $portal,
            ]);

            if (! $user) {
                throw new UnauthorizedHttpException('', __('auth.credentials_mismatch'));
            }

            if (! $user->email_verified_at) {
                $this->users->update($user, ['email_verified_at' => now()]);
            }

            $this->socialAccounts->createForUser($user, $provider, $socialUser);

            return $user->refresh();
        });

        if ($user->type !== $portal || ! UserActivationChecker::isActive($user, $portal)) {
            throw new UnauthorizedHttpException('', __('auth.credentials_mismatch'));
        }

        return array_merge(['user' => $user], $this->tokenIssuer->refreshToken($user, $portal));
    }
}
