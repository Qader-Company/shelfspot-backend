<?php

namespace App\Modules\V1\Reports\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Reports\Application\Services\CompanyDashboardReportService;
use App\Modules\V1\Reports\Presentation\Http\Requests\CompanyDashboardReportRequest;

class CompanyDashboardReportController extends Controller
{
    public function __construct(
        private readonly CompanyDashboardReportService $dashboardReportService,
        private readonly TenantContextInterface $tenantContext,
    ) {
    }

    public function dashboard(CompanyDashboardReportRequest $request)
    {
        return ApiResponse::success(
            $this->dashboardReportService->dashboard(
                companyId: $this->tenantContext->getCompanyId(),
                period: $request->validated('period')
            )
        );
    }
}
