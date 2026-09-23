<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Authentication\Domain\Services\OtpService;
use App\Modules\V1\Authentication\Domain\Services\TokenIssuer;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Users\Application\Services\DeviceTokenManager;
use App\Modules\V1\Users\Application\Services\UserActivationChecker;
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
        private UserRepositoryInterface $userRepository,
        private DeviceTokenManager $deviceTokens,
    ) {}

    public function execute(array $credentials, PortalTypeEnum $userType)
    {
        $user = $this->userRepository->findWhere([
            'email' => $credentials['email'],
            'type' => $userType,
        ]);

        $isValidCredentials = (
            $user &&
            UserActivationChecker::isActive($user, $userType) &&
            Hash::check($credentials['password'], $user->password)
        );

        if (! $isValidCredentials) {
            throw new UnauthorizedHttpException('', __('auth.credentials_mismatch'));
        }

        $needsVerification = is_null($user->email_verified_at);

        if (! $needsVerification && $userType === PortalTypeEnum::WORKER) {
            $this->deviceTokens->registerWorkerDevice($user, $credentials);
        }

        return $this->userDataDependedOnVerificationStatus(
            $user,
            $userType,
            $needsVerification,
        );
    }

    private function userDataDependedOnVerificationStatus(User $user, PortalTypeEnum $userType, bool $needsVerification)
    {
        $data = [
            'data' => ['user' => $user],
            'message' => __('auth.login_success'),
            'code' => Response::HTTP_OK,
        ];

        if ($needsVerification) {
            $data['data']['verification_token'] = $this->tokenIssuer->create($user, $userType, TokenTypeEnum::VERIFY_TOKEN);
            $data['message'] = __('auth.verify_account');
            $data['code'] = Response::HTTP_FORBIDDEN;
            $this->otpService->generateAndSend(
                $user->email,
                OtpPurposeEnum::EMAIL_VERIFICATION,
                $userType,
                $user->name
            );
        } else {
            $data['data'] = array_merge($data['data'], $this->tokenIssuer->refreshToken($user, $userType));
        }

        return $data;
    }
}
