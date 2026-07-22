<?php

namespace App\Modules\V1\Companies\Presentation\Http\Companies;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Companies\Domain\Repositories\CompanyRepositoryInterface;
use App\Modules\V1\Companies\Presentation\Http\Requests\UpdateCompanyProfileRequest;
use App\Modules\V1\Companies\Presentation\Http\Resources\CompanyResource;

class CompanyProfileController extends Controller
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly TenantContextInterface $tenantContext,
    ) {
    }

    public function show()
    {
        return ApiResponse::success(
            new CompanyResource($this->tenantContext->getCompany())
        );
    }

    public function update(UpdateCompanyProfileRequest $request)
    {
        $company = $this->companyRepository->update(
            $this->tenantContext->getCompany(),
            $request->validated(),
        );

        return ApiResponse::updated(new CompanyResource($company->refresh()));
    }
}
