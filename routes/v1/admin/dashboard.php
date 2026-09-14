<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Reports\Presentation\Http\Controllers\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::controller(AdminDashboardController::class)->group(function () {
    Route::get('/', 'show')
        ->middleware('permission:'.AdminPermissionEnum::VIEW_DASHBOARD->value);
});
