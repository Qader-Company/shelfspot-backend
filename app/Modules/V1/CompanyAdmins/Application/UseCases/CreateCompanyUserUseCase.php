<?php

namespace App\Modules\V1\CompanyAdmins\Application\UseCases;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\CompanyAdmins\Application\Services\CompanyUserManagementService;
use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;

class CreateCompanyUserUseCase
{
    public function __construct(
        private readonly CompanyUserManagementService $companyUsers,
    ) {}

    public function execute(Company $company, array $attributes, bool $isOwner = false): CompanyUser
    {
        $user = $this->companyUsers->create(
            companyId: $company->id,
            attributes: $attributes,
            isOwner: $isOwner,
            markEmailVerified: false,
        );

        return $user->companyUser->load(['company', 'user']);
    }
}
