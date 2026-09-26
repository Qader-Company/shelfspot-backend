<?php

namespace App\Modules\V1\Authentication\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Modules\V1\Authentication\Application\UseCases\ResetPasswordUseCase;
use App\Modules\V1\Authentication\Application\UseCases\VerifyResetPasswordOTPUseCase;
use App\Modules\V1\Authentication\Presentation\Http\Requests\ResetPasswordRequest;
use App\Modules\V1\Authentication\Presentation\Http\Requests\VerifyResetPasswordOTPRequest;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

class ResetPasswordController
{
    public function verifyResetPassOTP(
        VerifyResetPasswordOTPRequest $request,
        string $type,
        VerifyResetPasswordOTPUseCase $verifyResetPasswordOTPUseCase,
    ) {
        $portalType = PortalTypeEnum::tryFrom($type);

        $token = $verifyResetPasswordOTPUseCase->execute(
            $request->validated(),
            $portalType
        );

        return ApiResponse::success($token);
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPasswordUseCase $resetPasswordUseCase)
    {
        $resetPasswordUseCase->execute(
            $request->user(),
            $request->validated('password'),
        );

        return ApiResponse::message(__('auth.password_reset_success'));
    }
}
