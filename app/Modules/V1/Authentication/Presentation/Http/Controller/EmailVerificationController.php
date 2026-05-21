<?php

namespace App\Modules\V1\Authentication\Presentation\Http\Controller;


use App\Facades\ApiResponse;
use App\Modules\V1\Authentication\Application\UseCases\SendOtpUseCase;
use App\Modules\V1\Authentication\Application\UseCases\VerifyEmailUseCase;
use App\Modules\V1\Authentication\Presentation\Http\Requests\EmailValidationRequest;
use App\Modules\V1\Authentication\Presentation\Http\Requests\OTPValidationRequest;
use App\Modules\V1\Users\Application\Services\UserFormattingService;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

class EmailVerificationController
{
    public function verifyEmail(OTPValidationRequest $request, string $type, VerifyEmailUseCase $emailVerifyingUseCase)
    {
        $portalType = PortalTypeEnum::tryFrom($type);

        $data = $emailVerifyingUseCase->execute(
            $request->user(),
            $request->validated(),
            $portalType
        );

        $data['user'] = UserFormattingService::userFormat(
            $data['user'],
            $portalType
        );

        return ApiResponse::success(
            data: $data,
            message: __('auth.verified_success')
        );
    }

}
