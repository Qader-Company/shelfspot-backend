<?php

use App\Modules\V1\Companies\Presentation\Http\Companies\CompanyProfileController;
use App\Modules\V1\Users\Presentation\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::controller(ProfileController::class)->group(function () {
    Route::get('/', 'show');
    Route::match(['put', 'patch'], '/', 'update');
});

Route::prefix('company')
    ->controller(CompanyProfileController::class)
    ->group(function () {
        Route::get('/', 'show');
//        ->middleware('permission:'.CompanyPermissionEnum::VIEW_COMPANY->value);
        Route::match(['put', 'patch'], '/', 'update');
//        ->middleware('permission:'.CompanyPermissionEnum::EDIT_COMPANY->value);
    });
