<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Authentication\Domain\Services\OtpService;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Authentication\Domain\Services\TokenIssuer;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LogInUseCase
{

    public function __construct(
        private TokenIssuer $tokenIssuer,
        private OtpService $otpService,
        private UserRepositoryInterface $userRepository
    )
    {
    }

    public function execute(array $credentials, PortalTypeEnum $userType)
    {
        $user = $this->userRepository->findWhere([
            'email' => $credentials['email'],
            'type' => $userType,
        ]);

        if(!$user || !Hash::check($credentials['password'], $user->password))
            throw new UnauthorizedHttpException('', __('auth.credentials_mismatch'));

        return $this->userDataDependedOnVerificationStatus(
            $user,
            $userType,
            is_null($user->email_verified_at)
        );
    }

    private function userDataDependedOnVerificationStatus(User $user, PortalTypeEnum $userType, bool $isVerified)
    {
        $data = [
            'data' => ['user' => $user,],
            'message' => __('auth.login_success'),
            'code' => Response::HTTP_OK,
        ];

        if($isVerified){
            $data['data']['verify_token'] = $this->tokenIssuer->create($user, $userType, TokenTypeEnum::VERIFY_TOKEN);
            $data['message'] = __('auth.verify_account');
            $data['code'] = Response::HTTP_FORBIDDEN;
            $this->otpService->generateAndSend(
                $user->email,
                OtpPurposeEnum::EMAIL_VERIFICATION,
                $user->name
            );
        } else {
            $data['data'] = array_merge($data['data'], $this->tokenIssuer->refreshToken($user, $userType));
        }

        return $data;
    }
}
