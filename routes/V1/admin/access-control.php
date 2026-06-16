<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\AccessControl\Presentation\Http\Controllers\ShelfSpotAdminManagementController;
use Illuminate\Support\Facades\Route;

Route::controller(ShelfSpotAdminManagementController::class)
    ->group(function () {
        Route::get('/permissions', 'permissions')->middleware('permission:'.AdminPermissionEnum::VIEW_ROLE->value);
        Route::get('/roles', 'roles')->middleware('permission:'.AdminPermissionEnum::VIEW_ROLE->value);
        Route::post('/roles', 'storeRole')->middleware('permission:'.AdminPermissionEnum::CREATE_ROLE->value);
        Route::match(['put', 'patch'], '/roles/{roleId}', 'updateRole')->middleware('permission:'.AdminPermissionEnum::EDIT_ROLE->value);
        Route::delete('/roles/{roleId}', 'destroyRole')->middleware('permission:'.AdminPermissionEnum::DELETE_ROLE->value);
        Route::get('/admins', 'admins')->middleware('permission:'.AdminPermissionEnum::VIEW_ADMIN->value);
        Route::post('/admins', 'storeAdmin')->middleware('permission:'.AdminPermissionEnum::CREATE_ADMIN->value);
        Route::match(['put', 'patch'], '/admins/{user}', 'updateAdmin')->middleware('permission:'.AdminPermissionEnum::EDIT_ADMIN->value);
    });
