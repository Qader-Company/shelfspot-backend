<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Authentication\Domain\Contracts\SocialAccountRepositoryInterface;
use App\Modules\V1\Authentication\Domain\Services\TokenIssuer;
use App\Modules\V1\Authentication\Domain\ValueObjects\SocialProviderEnum;
use App\Modules\V1\Authentication\Infrastructure\Social\SocialProviderManager;
use App\Modules\V1\Companies\Application\UseCases\CreateCompanyWithOwnerUseCase;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\Users\Application\Services\UserActivationChecker;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Application\UseCases\CreateWorkerUseCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class SocialLoginUseCase
{
    public function __construct(
        private SocialProviderManager $providers,
        private SocialAccountRepositoryInterface $socialAccounts,
        private UserRepositoryInterface $users,
        private TokenIssuer $tokenIssuer,
        private CreateWorkerUseCase $createWorkerUseCase,
        private CreateCompanyWithOwnerUseCase $createCompanyWithOwnerUseCase,
    ) {
    }

    public function execute(SocialProviderEnum $provider, PortalTypeEnum $portal, string $token, array $attributes = []): array
    {
        $socialUser = $this->providers->driver($provider)->verify($token);

        if (! $socialUser->emailVerified) {
            throw new UnauthorizedHttpException('', __('auth.credentials_mismatch'));
        }

        $user = DB::transaction(function () use ($provider, $portal, $socialUser, $attributes) {
            $socialAccount = $this->socialAccounts->find($provider, $socialUser->providerUserId);

            if ($socialAccount) {
                return $socialAccount->user;
            }

            $user = $this->users->findWhere([
                'email' => $socialUser->email,
                'type' => $portal,
            ]);

            if (! $user) {
                $user = $this->registerSocialUser($portal, $socialUser->email, $socialUser->name, $attributes);
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

    private function registerSocialUser(PortalTypeEnum $portal, string $email, ?string $name, array $attributes)
    {
        return match ($portal) {
            PortalTypeEnum::WORKER => $this->registerWorker($email, $name, $attributes),
            PortalTypeEnum::COMPANY => $this->registerCompany($email, $name, $attributes),
            PortalTypeEnum::ADMIN => throw new UnauthorizedHttpException('', __('auth.credentials_mismatch')),
        };
    }

    private function registerWorker(string $email, ?string $name, array $attributes)
    {
        $data = Validator::make($attributes, [
            'phone' => ['required', 'string', 'max:255', 'unique:workers,phone'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ])->validate();

        return $this->createWorkerUseCase->execute(array_merge($data, [
            'name' => $name ?: $email,
            'email' => $email,
            'password' => Str::password(32),
        ]));
    }

    private function registerCompany(string $email, ?string $name, array $attributes)
    {
        $data = Validator::make(array_merge($attributes, ['email' => $email]), [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:companies,email'],
            'phone' => ['required', 'string', 'max:255', 'unique:companies,phone'],
            'cr_number' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', Rule::in(CompanyIndustryEnum::values())],
        ])->validate();

        return $this->createCompanyWithOwnerUseCase
            ->execute(array_merge($data, [
                'name' => $data['name'] ?? $name ?? $email,
                'email' => $email,
                'password' => Str::password(32),
            ]))
            ->users
            ->first()
            ->user;
    }
}
