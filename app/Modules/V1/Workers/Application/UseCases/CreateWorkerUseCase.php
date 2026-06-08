<?php

namespace App\Modules\V1\Workers\Application\UseCases;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CreateWorkerUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly WorkerRepositoryInterface $workerRepository,
    ) {
    }

    public function execute(array $attributes): User
    {
        return DB::transaction(function () use ($attributes) {
            $user = $this->userRepository->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
                'type' => PortalTypeEnum::WORKER,
            ]);

            $this->workerRepository->create([
                'user_id' => $user->id,
                'phone' => $attributes['phone'],
                'is_active' => true,
            ]);

            return $user->load('worker');
        });
    }
}
