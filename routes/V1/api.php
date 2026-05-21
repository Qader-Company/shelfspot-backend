<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Authentication\Presentation\Http\Controller\AuthController;
use Illuminate\Support\Facades\Route;
use App\Modules\V1\Authentication\Presentation\Http\Controller\ResetPasswordController;
use App\Modules\V1\Authentication\Presentation\Http\Controller\EmailVerificationController;

Route::prefix('auth')
    ->group(function (){
        Route::post('{type}/register', [AuthController::class, 'register'])
            ->where('type', PortalTypeEnum::COMPANY->value);

        Route::post('{type}/login', [AuthController::class, 'login'])
            ->where('type', implode('|', PortalTypeEnum::values()));

        Route::delete('/logout', [AuthController::class, 'logout'])
            ->middleware('auth:sanctum');

        Route::post('{type}/refresh', [AuthController::class, 'refreshToken'])
            ->middleware(['auth:sanctum', 'abilities:'. TokenTypeEnum::REFRESH_TOKEN->value]);

        Route::post('/send-otp', [AuthController::class, 'sendOTP']);

        //////////////// verify email \\\\\\\\\\\\\\\\
        Route::patch('{type}/email-verification', [EmailVerificationController::class, 'verifyEmail'])
            ->where('type', PortalTypeEnum::COMPANY->value)
            ->middleware(['auth:sanctum', 'abilities:'. TokenTypeEnum::VERIFY_TOKEN->value]);

        /////////////// reset password \\\\\\\\\\\\\\\\
        Route::post('{type}/reset-password-verification', [ResetPasswordController::class, 'verifyResetPassOTP'])
            ->where('type', implode('|', PortalTypeEnum::values()));

        Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])
            ->middleware(['auth:sanctum', 'abilities:'. TokenTypeEnum::RESET_PASSWORD_TOKEN->value]);
    });
