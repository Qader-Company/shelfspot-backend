<?php

namespace App\Modules\V1\Companies\Application\UseCases;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\Repositories\CompanyRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CreateCompanyWithOwnerUseCase
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CreateCompanyUserUseCase $createCompanyUserUseCase,
    ) {
    }

    public function execute(array $attributes): Company
    {
        return DB::transaction(function () use ($attributes) {
            $company = $this->companyRepository->create($attributes);
            $this->createCompanyUserUseCase->execute($company, $attributes, true);

            return $company->load('users.user');
        });
    }
}
