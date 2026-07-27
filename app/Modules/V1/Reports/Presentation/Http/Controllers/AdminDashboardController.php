<?php

namespace App\Modules\V1\Reports\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Reports\Application\Services\AdminDashboardService;
use App\Modules\V1\Reports\Presentation\Http\Requests\AdminDashboardRequest;

class AdminDashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboardService) {}

    public function show(AdminDashboardRequest $request)
    {
        return ApiResponse::success(
            $this->dashboardService->dashboard($request->validated('period'))
        );
    }
}
