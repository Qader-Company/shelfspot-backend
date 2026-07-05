<?php

namespace App\Modules\V1\Authentication\Infrastructure\Social\Registrars;

use App\Modules\V1\Authentication\Domain\Contracts\SocialPortalRegistrarInterface;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Application\UseCases\CreateWorkerUseCase;
use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WorkerSocialRegistrar implements SocialPortalRegistrarInterface
{
    public function __construct(
        private CreateWorkerUseCase $createWorkerUseCase,
        private WorkerRepositoryInterface $workerRepository,
    ) {
    }

    public function portal(): PortalTypeEnum
    {
        return PortalTypeEnum::WORKER;
    }

    public function register(string $email, ?string $name, array $attributes): User
    {
        return $this->createWorkerUseCase->execute(array_merge(
            $this->validatedProfileData($attributes),
            [
                'name' => $name ?: $email,
                'email' => $email,
                'password' => Str::password(32),
            ]
        ));
    }

    public function ensureProfile(User $user, array $attributes): User
    {
        $user->loadMissing('worker');

        if ($user->worker) {
            return $user;
        }

        $this->workerRepository->create(array_merge(
            $this->validatedProfileData($attributes),
            [
                'user_id' => $user->id,
                'is_active' => true,
                'location_updated_at' => isset($attributes['latitude'], $attributes['longitude']) ? now() : null,
            ]
        ));

        return $user->refresh()->load('worker');
    }

    private function validatedProfileData(array $attributes): array
    {
        return Validator::make(
            $attributes,
            config('social_auth.portal_profile_creation_rules.' . $this->portal()->value, [])
        )->validate();
    }
}
