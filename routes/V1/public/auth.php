<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Authentication\Presentation\Http\Controller\AuthController;
use Illuminate\Support\Facades\Route;
use App\Modules\V1\Authentication\Presentation\Http\Controller\ResetPasswordController;
use App\Modules\V1\Authentication\Presentation\Http\Controller\EmailVerificationController;


    Route::post('{type}/register', [AuthController::class, 'register'])
        ->where('type', PortalTypeEnum::COMPANY->value .'|'. PortalTypeEnum::WORKER->value)
            ->middleware('throttle:auth-register');

    Route::post('{type}/login', [AuthController::class, 'login'])
        ->where('type', implode('|', PortalTypeEnum::values()))
        ->middleware('throttle:auth-login');

    Route::delete('/logout', [AuthController::class, 'logout'])
        ->middleware(['auth:sanctum', 'throttle:auth-logout']);

    Route::post('{type}/refresh', [AuthController::class, 'refreshToken'])
        ->middleware([
            'auth:sanctum',
            'abilities:'. TokenTypeEnum::REFRESH_TOKEN->value,
            'throttle:auth-refresh',
        ]);

    Route::post('{purpose}/send-otp', [AuthController::class, 'sendOTP'])
        ->where('purpose', implode('|', OtpPurposeEnum::values()))
        ->middleware('throttle:auth-otp-send');

    //////////////// verify email \\\\\\\\\\\\\\\\
    Route::patch('{type}/email-verification', [EmailVerificationController::class, 'verifyEmail'])
        ->where('type', PortalTypeEnum::COMPANY->value .'|'. PortalTypeEnum::WORKER->value)
        ->middleware([
            'auth:sanctum',
            'abilities:'. TokenTypeEnum::VERIFY_TOKEN->value,
            'throttle:auth-otp-verify',
        ]);

    /////////////// reset password \\\\\\\\\\\\\\\\
    Route::post('{type}/reset-password-verification', [ResetPasswordController::class, 'verifyResetPassOTP'])
        ->where('type', implode('|', PortalTypeEnum::values()))
        ->middleware('throttle:auth-otp-verify');

    Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])
        ->middleware([
            'auth:sanctum',
            'abilities:'. TokenTypeEnum::RESET_PASSWORD_TOKEN->value,
            'throttle:auth-reset-password',
        ]);
