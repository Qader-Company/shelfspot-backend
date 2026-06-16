<?php

use App\Modules\V1\AccessControl\Presentation\Http\Controllers\CompanyAdminManagementController;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\Route;

Route::controller(CompanyAdminManagementController::class)
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
