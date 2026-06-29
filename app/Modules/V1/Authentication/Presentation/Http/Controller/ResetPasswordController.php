<?php

namespace App\Modules\V1\Authentication\Presentation\Http\Controller;


use App\Facades\ApiResponse;
use App\Modules\V1\Authentication\Application\UseCases\VerifyResetPasswordOTPUseCase;
use App\Modules\V1\Authentication\Presentation\Http\Requests\ResetPasswordRequest;
use App\Modules\V1\Authentication\Presentation\Http\Requests\VerifyResetPasswordOTPRequest;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\DB;

class ResetPasswordController
{


    public function verifyResetPassOTP(VerifyResetPasswordOTPRequest $request, string $type, VerifyResetPasswordOTPUseCase $verifyResetPasswordOTPUseCase )
    {
        $portalType = PortalTypeEnum::tryFrom($type);

        $token = $verifyResetPasswordOTPUseCase->execute(
            $request->validated(),
            $portalType
        );

        return ApiResponse::success($token);
    }

    public function resetPassword(ResetPasswordRequest $request, UserRepositoryInterface $userRepository)
    {
        DB::transaction(function () use ($request, $userRepository) {
            $user = $userRepository->update(
                user: $request->user(),
                attributes: ['password' => $request->validated('password')]
            );
            $user->tokens()->delete();
        });

        return ApiResponse::message(__('auth.password_reset_success'));
    }

}
