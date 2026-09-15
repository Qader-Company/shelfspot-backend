<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Products\Presentation\Http\Controller\ProductController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

Route::controller(ProductController::class)->group(function () {
        Route::prefix('trash')->group(function (){
            Route::get('', 'trash')->middleware('permission:'.CompanyPermissionEnum::VIEW_PRODUCT->value);
            Route::post('/bulk-restore', 'bulkRestore')->middleware('permission:'.CompanyPermissionEnum::EDIT_PRODUCT->value);
            Route::delete('/bulk-force-delete', 'bulkForceDelete')->middleware('permission:'.CompanyPermissionEnum::DELETE_PRODUCT->value);
            Route::post('/{id}/restore', 'restore')->middleware('permission:'.CompanyPermissionEnum::EDIT_PRODUCT->value);
            Route::delete('/{id}', 'forceDelete')->middleware('permission:'.CompanyPermissionEnum::DELETE_PRODUCT->value);

        });
        Route::prefix('excel')->group(function (){
            Route::get('/template', 'excelTemplate')->middleware('permission:'.CompanyPermissionEnum::VIEW_PRODUCT->value);
            Route::get('/export', 'excelExport')->middleware('permission:'.CompanyPermissionEnum::VIEW_PRODUCT->value);
            Route::post('/import', 'excelImport')->middleware('permission:'.CompanyPermissionEnum::CREATE_PRODUCT->value);
        });

        Route::get('/filter-options', 'filterOptions')->middleware('permission:'.CompanyPermissionEnum::VIEW_PRODUCT->value);
        Route::get('/', 'index')->middleware('permission:'.CompanyPermissionEnum::VIEW_PRODUCT->value);
        Route::post('/', 'store')->middleware('permission:'.CompanyPermissionEnum::CREATE_PRODUCT->value);
        Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:'.CompanyPermissionEnum::DELETE_PRODUCT->value);
        Route::get('/{id}', 'show')->middleware('permission:'.CompanyPermissionEnum::VIEW_PRODUCT->value);
        Route::match(['put', 'patch'], '/{id}', 'update')->middleware('permission:'.CompanyPermissionEnum::EDIT_PRODUCT->value);
        Route::delete('/{id}', 'destroy')->middleware('permission:'.CompanyPermissionEnum::DELETE_PRODUCT->value);

    });
