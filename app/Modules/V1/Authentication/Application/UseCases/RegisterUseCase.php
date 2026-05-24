<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Authentication\Domain\Services\OtpService;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Companies\Application\UseCases\CreateCompanyUserUseCase;
use App\Modules\V1\Companies\Domain\Repositories\CompanyRepositoryInterface;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Authentication\Domain\Services\TokenIssuer;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            };

            $this->otpService->generateAndSend(
                $attributes['email'],
                OtpPurposeEnum::EMAIL_VERIFICATION,
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


    private function registerCompanyUser(array $attributes): User
    {
        $company = app(CompanyRepositoryInterface::class)->create([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'phone' => $attributes['phone'],
            'cr_number' => $attributes['cr_number'],
            'industry' => $attributes['industry'],
            'type' => PortalTypeEnum::COMPANY
        ]);

        $companyUser = app(CreateCompanyUserUseCase::class)->execute($company, $attributes, true);

        return $companyUser->user->load('companyUser.company');
    }

}

