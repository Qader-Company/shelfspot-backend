<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Authentication\Domain\Services\OtpService;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Authentication\Domain\Services\TokenIssuer;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class VerifyEmailUseCase
{

    public function __construct(
        private readonly TokenIssuer $tokenHandler,
        private readonly OtpService  $otpService
    )
    {
    }

    public function execute(User $user, array $data, PortalTypeEnum $type)
    {
        if ($user->email_verified_at)
            throw new BadRequestHttpException(__('auth.already_verified'));

        if (! $this->otpService->validate($user->email, $data['otp']))
            throw new BadRequestHttpException(__('auth.invalid_otp'));


        $user->update(['email_verified_at' => now()]);

        $tokens = $this->tokenHandler->refreshToken(
            user: $user,
            portal: $type,
        );

        return $tokens + [
            'user' => $user,
        ];
    }

}
