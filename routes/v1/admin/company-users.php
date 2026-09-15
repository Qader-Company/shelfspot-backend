<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\CompanyAdmins\Presentation\Http\Controllers\AdminCompanyUserManagementController;
use Illuminate\Support\Facades\Route;

Route::controller(AdminCompanyUserManagementController::class)->group(function () {
    Route::get('/', 'index')
        ->middleware('permission:'.AdminPermissionEnum::VIEW_COMPANY_USER->value);
    Route::post('/', 'store')
        ->middleware('permission:'.AdminPermissionEnum::CREATE_COMPANY_USER->value);
    Route::get('/{user}', 'show')
        ->middleware('permission:'.AdminPermissionEnum::VIEW_COMPANY_USER->value);
    Route::patch('/{user}', 'update')
        ->middleware('permission:'.AdminPermissionEnum::EDIT_COMPANY_USER->value);
    Route::post('/{user}/reset-password', 'resetPassword')
        ->middleware('permission:'.AdminPermissionEnum::RESET_COMPANY_USER_PASSWORD->value);
    Route::delete('/{user}', 'destroy')
        ->middleware('permission:'.AdminPermissionEnum::DELETE_COMPANY_USER->value);
});
