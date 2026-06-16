<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Tasks\Presentation\Http\Controllers\CompanyTaskController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

Route::controller(CompanyTaskController::class)
    ->group(function () {
        Route::get('/', 'index')->middleware('permission:'.CompanyPermissionEnum::VIEW_TASK->value);
        Route::post('/', 'store')->middleware('permission:'.CompanyPermissionEnum::CREATE_TASK->value);
        Route::get('/{id}', 'show')->middleware('permission:'.CompanyPermissionEnum::VIEW_TASK->value);
        Route::delete('/{id}', 'destroy')->middleware('permission:'.CompanyPermissionEnum::DELETE_TASK->value);
    });
