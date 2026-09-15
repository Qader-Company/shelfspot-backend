<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\Reports\Presentation\Http\Controllers\CompanyDashboardReportController;

Route::controller(CompanyDashboardReportController::class)
    ->group(function () {
        Route::get('/dashboard', 'dashboard');
    });
