<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Categories\Presentation\Http\Controller\CategoryController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

Route::middleware('abilities:'. PortalTypeEnum::COMPANY->value .','. TokenTypeEnum::ACCESS_TOKEN->value)
    ->controller(CategoryController::class)->group(function () {
        Route::prefix('trash')->group(function (){
            Route::get('', 'trash')->middleware('permission:'.CompanyPermissionEnum::VIEW_CATEGORY->value);
            Route::post('/bulk-restore', 'bulkRestore')->middleware('permission:'.CompanyPermissionEnum::EDIT_CATEGORY->value);
            Route::delete('/bulk-force-delete', 'bulkForceDelete')->middleware('permission:'.CompanyPermissionEnum::DELETE_CATEGORY->value);
            Route::post('/{id}/restore', 'restore')->middleware('permission:'.CompanyPermissionEnum::EDIT_CATEGORY->value);
            Route::delete('/{id}', 'forceDelete')->middleware('permission:'.CompanyPermissionEnum::DELETE_CATEGORY->value);
        });

        Route::get('/', 'index')
            ->middleware('permission:'.CompanyPermissionEnum::VIEW_CATEGORY->value);
        Route::post('/', 'store')
            ->middleware('permission:'.CompanyPermissionEnum::CREATE_CATEGORY->value);
        Route::post('/bulk-delete', 'bulkDelete')
            ->middleware('permission:'.CompanyPermissionEnum::DELETE_CATEGORY->value);
        Route::get('/{id}', 'show')
            ->middleware('permission:'.CompanyPermissionEnum::VIEW_CATEGORY->value);
        Route::match(['put', 'patch'], '/{id}', 'update')
            ->middleware('permission:'.CompanyPermissionEnum::EDIT_CATEGORY->value);
        Route::delete('/{id}', 'destroy')
            ->middleware('permission:'.CompanyPermissionEnum::DELETE_CATEGORY->value);

        Route::prefix('excel')->group(function (){
            Route::get('/template', 'excelTemplate')
                ->middleware('permission:'.CompanyPermissionEnum::VIEW_CATEGORY->value);
            Route::get('/export', 'excelExport')
                ->middleware('permission:'.CompanyPermissionEnum::VIEW_CATEGORY->value);
            Route::post('/import', 'excelImport')
                ->middleware('permission:'.CompanyPermissionEnum::CREATE_CATEGORY->value);
        });



    });
