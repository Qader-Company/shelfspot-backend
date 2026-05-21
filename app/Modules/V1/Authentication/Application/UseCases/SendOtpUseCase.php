<?php

namespace App\Modules\V1\Authentication\Application\UseCases;

use App\Modules\V1\Authentication\Domain\Services\OtpService;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Companies\Application\UseCases\CreateCompanyUserUseCase;
use App\Modules\V1\Companies\Domain\Repositories\CompanyRepositoryInterface;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Authentication\Domain\Services\TokenIssuer;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\DB;

class SendOtpUseCase
{

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private OtpService $otpService
    )
    {
    }

    public function execute(string $email): array
    {
        $user = $this->userRepository->findWhere(['email' => $email]);
        if($user) $this->otpService->generate($user->email);
    }


}
