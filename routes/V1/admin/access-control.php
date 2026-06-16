<?php

use App\Modules\V1\AccessControl\Presentation\Http\Controllers\ShelfSpotAdminManagementController;
use Illuminate\Support\Facades\Route;

Route::controller(ShelfSpotAdminManagementController::class)
    ->group(function () {
        Route::get('/permissions', 'permissions');
        Route::get('/roles', 'roles');
        Route::post('/roles', 'storeRole');
        Route::match(['put', 'patch'], '/roles/{roleId}', 'updateRole');
        Route::delete('/roles/{roleId}', 'destroyRole');
        Route::get('/admins', 'admins');
        Route::post('/admins', 'storeAdmin');
        Route::match(['put', 'patch'], '/admins/{user}', 'updateAdmin');
    });
