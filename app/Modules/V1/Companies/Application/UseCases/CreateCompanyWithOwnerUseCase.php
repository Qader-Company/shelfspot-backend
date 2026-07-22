<?php

namespace App\Modules\V1\Companies\Application\UseCases;

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\Repositories\CompanyRepositoryInterface;
use App\Modules\V1\CompanyAdmins\Application\UseCases\CreateCompanyUserUseCase;
use Illuminate\Support\Facades\DB;

class CreateCompanyWithOwnerUseCase
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CreateCompanyUserUseCase $createCompanyUserUseCase,
        private readonly FullAccessRoleProvisioner $fullAccessRoleProvisioner,
    ) {
    }

    public function execute(array $attributes): Company
    {
        return DB::transaction(function () use ($attributes) {
            $company = $this->companyRepository->create($attributes);
            $attributes['name'] = $attributes['name'] . ' Owner';
            $companyUser = $this->createCompanyUserUseCase->execute($company, $attributes, true);

            $this->fullAccessRoleProvisioner->assignCompanyOwnerRole($companyUser->user, $company->id);

            return $company->load('users.user');
        });
    }
}
