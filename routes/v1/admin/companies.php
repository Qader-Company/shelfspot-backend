<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Companies\Presentation\Http\Companies\CompanyController;

Route::controller(CompanyController::class)->group(function (){
        Route::prefix('trash')->group(function (){
            Route::get('', 'trash')->middleware('permission:'.AdminPermissionEnum::VIEW_COMPANY->value);
            Route::post('/bulk-restore', 'bulkRestore')->middleware('permission:'.AdminPermissionEnum::EDIT_COMPANY->value);
            Route::delete('/bulk-force-delete', 'bulkForceDelete')->middleware('permission:'.AdminPermissionEnum::DELETE_COMPANY->value);
            Route::post('/{id}/restore', 'restore')->middleware('permission:'.AdminPermissionEnum::EDIT_COMPANY->value);
            Route::delete('/{id}', 'forceDelete')->middleware('permission:'.AdminPermissionEnum::DELETE_COMPANY->value);
        });
        Route::get('/', 'index')->middleware('permission:'.AdminPermissionEnum::VIEW_COMPANY->value);
        Route::post('/', 'create')->middleware('permission:'.AdminPermissionEnum::CREATE_COMPANY->value);
        Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:'.AdminPermissionEnum::DELETE_COMPANY->value);
        Route::get('/{company}', 'show')->middleware('permission:'.AdminPermissionEnum::VIEW_COMPANY->value);
        Route::match(['put', 'patch'], '/{company}', 'update')->middleware('permission:'.AdminPermissionEnum::EDIT_COMPANY->value);
        Route::delete('/{company}', 'destroy')->middleware('permission:'.AdminPermissionEnum::DELETE_COMPANY->value);
    });
