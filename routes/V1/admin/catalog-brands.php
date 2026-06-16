<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Brands\Presentation\Http\Controller\BrandController;
use Illuminate\Support\Facades\Route;

Route::controller(BrandController::class)
    ->group(function () {
        $catalogPolicy = 'permission:'.AdminPermissionEnum::MANAGE_COMPANY_CATALOG->value;

        Route::prefix('excel')->group(function () use ($catalogPolicy) {
            Route::get('/template', 'excelTemplate')
                ->middleware($catalogPolicy);
            Route::get('/export', 'excelExport')
                ->middleware($catalogPolicy);
            Route::post('/import', 'excelImport')
                ->middleware($catalogPolicy);
        });

        Route::get('/', 'index')->middleware($catalogPolicy);
        Route::post('/', 'store')->middleware($catalogPolicy);
        Route::get('/{id}', 'show')->middleware($catalogPolicy);
        Route::match(['put', 'patch'], '/{id}', 'update')->middleware($catalogPolicy);
        Route::delete('/{id}', 'destroy')->middleware($catalogPolicy);
        Route::post('/bulk-delete', 'bulkDelete')->middleware($catalogPolicy);

        Route::prefix('trash')->group(function () use ($catalogPolicy) {
            Route::get('/', 'trash')
                ->middleware($catalogPolicy);
            Route::post('/bulk-restore', 'bulkRestore')
                ->middleware($catalogPolicy);
            Route::delete('/bulk-force-delete', 'bulkForceDelete')
                ->middleware($catalogPolicy);
            Route::post('/{id}/restore', 'restore')
                ->middleware($catalogPolicy);
            Route::delete('/{id}', 'forceDelete')
                ->middleware($catalogPolicy);
        });
    });
