<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Authentication\Domain\Contracts\SocialAccountRepositoryInterface;
use App\Modules\V1\Authentication\Domain\Services\TokenIssuer;
use App\Modules\V1\Authentication\Domain\ValueObjects\SocialProviderEnum;
use App\Modules\V1\Authentication\Infrastructure\Social\SocialPortalRegistrarManager;
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
        private SocialPortalRegistrarManager $registrars,
        private SocialAccountRepositoryInterface $socialAccounts,
        private UserRepositoryInterface $users,
        private TokenIssuer $tokenIssuer,
    ) {
    }

    public function execute(SocialProviderEnum $provider, PortalTypeEnum $portal, string $token, array $attributes = []): array
    {
        $registrar = $this->registrars->for($provider, $portal);
        $socialUser = $this->providers->driver($provider)->verify($token);

        if (! $socialUser->emailVerified) {
            throw new UnauthorizedHttpException('', __('auth.credentials_mismatch'));
        }

        $user = DB::transaction(function () use ($provider, $portal, $socialUser, $attributes, $registrar) {
            $socialAccount = $this->socialAccounts->find($provider, $socialUser->providerUserId);
            $user = $socialAccount?->user;

            if (! $user) {
                $user = $this->users->findWhere([
                    'email' => $socialUser->email,
                    'type' => $portal,
                ]);
            }

            if (! $user) {
                $user = $registrar->register($socialUser->email, $attributes['name'] ?? $socialUser->name, $attributes);
            }

            if ($user->type !== $portal) {
                throw new UnauthorizedHttpException('', __('auth.credentials_mismatch'));
            }

            $user = $registrar->ensureProfile($user, $attributes);

            if (! $user->email_verified_at) {
                $this->users->update($user, ['email_verified_at' => now()]);
            }

            if (! $socialAccount) {
                $this->socialAccounts->createForUser($user, $provider, $socialUser);
            }

            return $user->refresh();
        });

        if (! UserActivationChecker::isActive($user, $portal)) {
            throw new UnauthorizedHttpException('', __('auth.credentials_mismatch'));
        }

        return array_merge(['user' => $user], $this->tokenIssuer->refreshToken($user, $portal));
    }
}
