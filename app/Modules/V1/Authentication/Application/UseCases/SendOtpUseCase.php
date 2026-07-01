<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Authentication\Domain\Services\OtpService;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

class SendOtpUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private OtpService $otpService
    ) {
    }

    public function execute(string $email, OtpPurposeEnum $otpPurpose, ?PortalTypeEnum $portal = null): void
    {
        $criteria = ['email' => $email];
        if ($portal) {
            $criteria['type'] = $portal;
        }

        $user = $this->userRepository->findWhere($criteria);

        if ($user) {
            $this->otpService->generateAndSend(
                $user->email,
                $otpPurpose,
                $user->type,
                $user->name
            );
        }
    }
}
