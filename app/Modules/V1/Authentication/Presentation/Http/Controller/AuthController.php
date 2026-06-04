<?php

namespace App\Modules\V1\Authentication\Presentation\Http\Controller;


use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Authentication\Application\UseCases\RegisterUseCase;
use App\Modules\V1\Authentication\Application\UseCases\SendOtpUseCase;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Authentication\Presentation\Http\Requests\EmailValidationRequest;
use App\Modules\V1\Authentication\Presentation\Http\Requests\RegisterRequest;
use App\Modules\V1\Users\Application\Services\UserResourceResolver;
use App\Modules\V1\Authentication\Application\UseCases\LogInUseCase;
use App\Modules\V1\Authentication\Domain\Services\TokenIssuer;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Authentication\Presentation\Http\Requests\LoginRequest;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $loginRequest, string $type, LogInUseCase $loginUseCase)
    {
        $portalType = PortalTypeEnum::tryFrom($type);
        $data = $loginUseCase->execute(
            $loginRequest->validated(),
            $portalType
        );

        $loginData = $data['data'];

        $loginData['user'] = UserResourceResolver::resolve(
            $loginData['user'],
            $portalType
        );

        return ApiResponse::apiFormat(
            ['data' => $loginData],
            $data['message'],
            $data['code']
        );
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();
        return ApiResponse::message(__('auth.loggedOut'));
    }

    public function refreshToken(Request $request, string $type, TokenIssuer $tokenHandler)
    {
        $data = $tokenHandler->refreshToken(
            user: $request->user(),
            portal: PortalTypeEnum::tryFrom($type),
        );
        return ApiResponse::success($data, __('auth.token_refreshed'));
    }

    public function register(RegisterRequest $registerRequest, string $type, RegisterUseCase $registerUseCase)
    {
        $portalType = PortalTypeEnum::tryFrom($type);
        $data = $registerUseCase->execute(
            $registerRequest->validated(),
            $portalType
        );

        $data['user'] = UserResourceResolver::resolve(
            $data['user'],
            $portalType
        );

        return ApiResponse::success($data, __('auth.verify_account'));
    }

    public function sendOTP(EmailValidationRequest $request, string $purpose, SendOtpUseCase $sendOtpUseCase)
    {
        $otpPurpose = OtpPurposeEnum::tryFrom($purpose);
        $sendOtpUseCase->execute($request->email, $otpPurpose);
        return ApiResponse::message(__('auth.code_sent'));
    }
}
