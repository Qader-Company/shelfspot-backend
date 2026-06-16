<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\SubCategories\Presentation\Http\Controller\SubCategoryController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\Route;

Route::controller(SubCategoryController::class)
    ->group(function () {
        $catalogPolicy = 'permission:'.AdminPermissionEnum::MANAGE_COMPANY_CATALOG->value;

        Route::get('/excel/template', 'excelTemplate')->middleware($catalogPolicy);
        Route::get('/excel/export', 'excelExport')->middleware($catalogPolicy);
        Route::post('/excel/import', 'excelImport')->middleware($catalogPolicy);

        Route::get('/', 'index')->middleware($catalogPolicy);
        Route::post('/', 'store')->middleware($catalogPolicy);
        Route::get('/{id}', 'show')->middleware($catalogPolicy);
        Route::match(['put', 'patch'], '/{id}', 'update')->middleware($catalogPolicy);
        Route::delete('/{id}', 'destroy')->middleware($catalogPolicy);

        Route::get('/trash', 'trash')->middleware($catalogPolicy);
        Route::post('/bulk-delete', 'bulkDelete')->middleware($catalogPolicy);
        Route::post('/trash/bulk-restore', 'bulkRestore')->middleware($catalogPolicy);
        Route::delete('/trash/bulk-force-delete', 'bulkForceDelete')->middleware($catalogPolicy);
        Route::post('/trash/{id}/restore', 'restore')->middleware($catalogPolicy);
        Route::delete('/trash/{id}', 'forceDelete')->middleware($catalogPolicy);
    });
