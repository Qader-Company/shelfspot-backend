<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Authentication\Domain\Services\OtpService;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Authentication\Domain\Services\TokenIssuer;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class VerifyResetPasswordOTPUseCase
{

    public function __construct(
        private readonly TokenIssuer             $tokenHandler,
        private readonly OtpService              $otpService,
        private readonly UserRepositoryInterface $userRepository
    )
    {
    }

    public function execute(array $data, PortalTypeEnum $type)
    {
        $user = $this->userRepository->findWhere(['email' => $data['email']]);

        if(!$user)
            throw new BadRequestHttpException(__('auth.user_not_found'));

        $isOTPValid = $this->otpService->validate($data['email'], $data['otp']);

        if (! $isOTPValid)
            throw new BadRequestHttpException(__('auth.invalid_otp'));

        $resetToken = $this->tokenHandler->create(
            user: $user,
            portal: $type,
            tokenType: TokenTypeEnum::RESET_PASSWORD_TOKEN,
        );

        return ['reset_password_token' => $resetToken];
    }

}
