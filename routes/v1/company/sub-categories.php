<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\SubCategories\Presentation\Http\Controller\SubCategoryController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

Route::controller(SubCategoryController::class)->group(function () {
        Route::prefix('excel')->group(function (){
            Route::get('/template', 'excelTemplate')->middleware('permission:'.CompanyPermissionEnum::VIEW_SUB_CATEGORY->value);
            Route::get('/export', 'excelExport')->middleware('permission:'.CompanyPermissionEnum::VIEW_SUB_CATEGORY->value);
            Route::post('/import', 'excelImport')->middleware('permission:'.CompanyPermissionEnum::CREATE_SUB_CATEGORY->value);
        });
        Route::prefix('trash')->group(function (){
            Route::get('/trash', 'trash')->middleware('permission:'.CompanyPermissionEnum::VIEW_SUB_CATEGORY->value);
            Route::post('/trash/bulk-restore', 'bulkRestore')->middleware('permission:'.CompanyPermissionEnum::EDIT_SUB_CATEGORY->value);
            Route::delete('/trash/bulk-force-delete', 'bulkForceDelete')->middleware('permission:'.CompanyPermissionEnum::DELETE_SUB_CATEGORY->value);
            Route::post('/trash/{id}/restore', 'restore')->middleware('permission:'.CompanyPermissionEnum::EDIT_SUB_CATEGORY->value);
            Route::delete('/trash/{id}', 'forceDelete')->middleware('permission:'.CompanyPermissionEnum::DELETE_SUB_CATEGORY->value);
        });

        Route::get('/', 'index')->middleware('permission:'.CompanyPermissionEnum::VIEW_SUB_CATEGORY->value);
        Route::post('/', 'store')->middleware('permission:'.CompanyPermissionEnum::CREATE_SUB_CATEGORY->value);
        Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:'.CompanyPermissionEnum::DELETE_SUB_CATEGORY->value);
        Route::get('/{id}', 'show')->middleware('permission:'.CompanyPermissionEnum::VIEW_SUB_CATEGORY->value);
        Route::match(['put', 'patch'], '/{id}', 'update')->middleware('permission:'.CompanyPermissionEnum::EDIT_SUB_CATEGORY->value);
        Route::delete('/{id}', 'destroy')->middleware('permission:'.CompanyPermissionEnum::DELETE_SUB_CATEGORY->value);
    });
