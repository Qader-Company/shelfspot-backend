<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Authentication\Domain\Services\OtpService;
use App\Modules\V1\Authentication\Domain\Services\TokenIssuer;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Companies\Application\UseCases\CreateCompanyWithOwnerUseCase;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Application\UseCases\CreateWorkerUseCase;
use Illuminate\Support\Facades\DB;

class RegisterUseCase
{

    public function __construct(
        private TokenIssuer $tokenIssuer,
        private OtpService $otpService
    )
    {
    }

    public function execute(array $attributes, PortalTypeEnum $userType): array
    {
        return DB::transaction(function () use ($attributes, $userType){
            $user = match ($userType){
                PortalTypeEnum::COMPANY => $this->registerCompanyUser($attributes),
                PortalTypeEnum::WORKER => $this->registerWorker($attributes),
            };

            $this->otpService->generateAndSend(
                $attributes['email'],
                OtpPurposeEnum::EMAIL_VERIFICATION,
                $userType,
                $user->name
            );

            $verification_token = $this->tokenIssuer->create(
                $user,
                $userType,
                TokenTypeEnum::VERIFY_TOKEN
            );

            return [
                'user' => $user,
                'verification_token' => $verification_token,
            ];
        });

    }


    private function registerWorker(array $attributes): User
    {
        return app(CreateWorkerUseCase::class)->execute($attributes);
    }

    private function registerCompanyUser(array $attributes): User
    {
        $company = app(CreateCompanyWithOwnerUseCase::class)->execute($attributes);
        return $company->users->first()->user;
    }

}
