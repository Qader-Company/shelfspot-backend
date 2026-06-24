<?php

namespace App\Modules\V1\CompanyAdmins\Application\UseCases;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\CompanyAdmins\Domain\Repositories\CompanyAdminRepositoryInterface;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\DB;

class CreateCompanyUserUseCase
{
    public function __construct(
        private UserRepositoryInterface         $userRepository,
        private CompanyAdminRepositoryInterface $companyUserRepository,
    )
    {
    }

    public function execute(Company $company, array $attributes, bool $isOwner = false)
    {
        return DB::transaction(function () use ($attributes, $company, $isOwner){

            $user = $this->userRepository->create([
                'name' => $attributes['user_name'] ?? $attributes['name'] ?? 'Company Admin',
                'email' => $attributes['email'],
                'password' => $attributes['password'],
                'type' => PortalTypeEnum::COMPANY,
            ]);

            $companyUser = $this->companyUserRepository->create([
               'company_id' => $company->id,
               'user_id' => $user->id,
               'is_owner' => $isOwner,
            ]);

            return $companyUser->load(['company', 'user']);
        });
    }
}
