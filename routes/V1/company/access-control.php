<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\AccessControl\Presentation\Http\Controllers\CompanyAdminManagementController;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\Route;

Route::controller(CompanyAdminManagementController::class)
    ->group(function () {
        Route::get('/permissions', 'permissions')->middleware('permission:'.CompanyPermissionEnum::VIEW_ROLE->value);
        Route::get('/roles', 'roles')->middleware('permission:'.CompanyPermissionEnum::VIEW_ROLE->value);
        Route::post('/roles', 'storeRole')->middleware('permission:'.CompanyPermissionEnum::CREATE_ROLE->value);
        Route::match(['put', 'patch'], '/roles/{roleId}', 'updateRole')->middleware('permission:'.CompanyPermissionEnum::EDIT_ROLE->value);
        Route::delete('/roles/{roleId}', 'destroyRole')->middleware('permission:'.CompanyPermissionEnum::DELETE_ROLE->value);
        Route::get('/admins', 'admins')->middleware('permission:'.CompanyPermissionEnum::VIEW_ADMIN->value);
        Route::post('/admins', 'storeAdmin')->middleware('permission:'.CompanyPermissionEnum::CREATE_ADMIN->value);
        Route::match(['put', 'patch'], '/admins/{user}', 'updateAdmin')->middleware('permission:'.CompanyPermissionEnum::EDIT_ADMIN->value);
    });
