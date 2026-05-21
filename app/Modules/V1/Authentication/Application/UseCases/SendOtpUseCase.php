<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Authentication\Domain\Services\OtpService;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;

class SendOtpUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private OtpService $otpService
    ) {
    }

    public function execute(string $email, OtpPurposeEnum $otpPurpose): void
    {
        $user = $this->userRepository->findWhere(['email' => $email]);

        if ($user) {
            $this->otpService->generateAndSend(
                $user->email,
                $otpPurpose,
                $user->name
            );
        }
    }
}
