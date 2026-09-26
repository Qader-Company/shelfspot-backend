<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ResetPasswordUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function execute(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password): void {
            $this->userRepository->update($user, ['password' => $password]);
            $user->tokens()->delete();
        });
    }
}
