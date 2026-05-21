<?php

namespace App\Modules\V1\Companies\Application\UseCases;

use App\Modules\V1\Companies\Domain\Repositories\CompanyRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\DB;

class CreateCompanyUseCase
{
    public function __construct(
        private CompanyRepositoryInterface $companyRepository,
    )
    {
    }

    public function execute(array $attributes)
    {
        $company = $this->companyRepository->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'phone' => $attributes['phone'],
                'cr_number' => $attributes['cr_number'],
                'industry' => $attributes['industry'],
                'type' => PortalTypeEnum::COMPANY
            ]);
    }
}
