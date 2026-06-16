<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\Brands\Presentation\Http\Controller\BrandController;

Route::controller(BrandController::class)->group(function () {
        Route::get('/', 'index')
            ->middleware('permission:'.CompanyPermissionEnum::VIEW_BRAND->value);
        Route::post('/', 'store')
            ->middleware('permission:'.CompanyPermissionEnum::CREATE_BRAND->value);
        Route::get('/{id}', 'show')
            ->middleware('permission:'.CompanyPermissionEnum::VIEW_BRAND->value);
        Route::match(['put', 'patch'], '/{id}', 'update')
            ->middleware('permission:'.CompanyPermissionEnum::EDIT_BRAND->value);
        Route::delete('/{id}', 'destroy')
            ->middleware('permission:'.CompanyPermissionEnum::DELETE_BRAND->value);
        Route::post('/bulk-delete', 'bulkDelete')
            ->middleware('permission:'.CompanyPermissionEnum::DELETE_BRAND->value);

        Route::prefix('excel')->group(function () {
            Route::get('/template', 'excelTemplate')
                ->middleware('permission:' . CompanyPermissionEnum::VIEW_BRAND->value);
            Route::get('/export', 'excelExport')
                ->middleware('permission:' . CompanyPermissionEnum::VIEW_BRAND->value);
            Route::post('/import', 'excelImport')
                ->middleware('permission:' . CompanyPermissionEnum::CREATE_BRAND->value);
        });

        Route::prefix('trash')->group(function () {
            Route::get('', 'trash')
                ->middleware('permission:'.CompanyPermissionEnum::VIEW_BRAND->value);
            Route::post('/bulk-restore', 'bulkRestore')
                ->middleware('permission:'.CompanyPermissionEnum::EDIT_BRAND->value);
            Route::delete('/bulk-force-delete', 'bulkForceDelete')
                ->middleware('permission:'.CompanyPermissionEnum::DELETE_BRAND->value);
            Route::post('/{id}/restore', 'restore')
                ->middleware('permission:'.CompanyPermissionEnum::EDIT_BRAND->value);
            Route::delete('/{id}', 'forceDelete')
                ->middleware('permission:'.CompanyPermissionEnum::DELETE_BRAND->value);
        });

    });
