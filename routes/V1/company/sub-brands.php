<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\SubBrands\Presentation\Http\Controller\SubBrandController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

Route::controller(SubBrandController::class)->group(function () {
        Route::prefix('excel')->group(function (){
            Route::get('/template', 'excelTemplate')->middleware('permission:'.CompanyPermissionEnum::VIEW_SUB_BRAND->value);
            Route::get('/export', 'excelExport')->middleware('permission:'.CompanyPermissionEnum::VIEW_SUB_BRAND->value);
            Route::post('/import', 'excelImport')->middleware('permission:'.CompanyPermissionEnum::CREATE_SUB_BRAND->value);
        });
        Route::prefix('trash')->group(function (){
            Route::get('', 'trash')->middleware('permission:'.CompanyPermissionEnum::VIEW_SUB_BRAND->value);
            Route::post('/bulk-restore', 'bulkRestore')->middleware('permission:'.CompanyPermissionEnum::EDIT_SUB_BRAND->value);
            Route::delete('/bulk-force-delete', 'bulkForceDelete')->middleware('permission:'.CompanyPermissionEnum::DELETE_SUB_BRAND->value);
            Route::post('/{id}/restore', 'restore')->middleware('permission:'.CompanyPermissionEnum::EDIT_SUB_BRAND->value);
            Route::delete('/{id}', 'forceDelete')->middleware('permission:'.CompanyPermissionEnum::DELETE_SUB_BRAND->value);
        });

        Route::get('/', 'index')->middleware('permission:'.CompanyPermissionEnum::VIEW_SUB_BRAND->value);
        Route::post('/', 'store')->middleware('permission:'.CompanyPermissionEnum::CREATE_SUB_BRAND->value);
        Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:'.CompanyPermissionEnum::DELETE_SUB_BRAND->value);
        Route::get('/{id}', 'show')->middleware('permission:'.CompanyPermissionEnum::VIEW_SUB_BRAND->value);
        Route::match(['put', 'patch'], '/{id}', 'update')->middleware('permission:'.CompanyPermissionEnum::EDIT_SUB_BRAND->value);
        Route::delete('/{id}', 'destroy')->middleware('permission:'.CompanyPermissionEnum::DELETE_SUB_BRAND->value);
    });
