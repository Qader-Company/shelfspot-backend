<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\Shared\Presentation\Http\Requests\BulkActionRequest;
use App\Modules\Shared\Presentation\Http\Requests\ImportExcelRequest;
use App\Modules\V1\SubBrands\Presentation\Http\Controller\SubBrandController;
use App\Modules\V1\SubBrands\Presentation\Http\Requests\StoreSubBrandRequest;
use App\Modules\V1\SubBrands\Presentation\Http\Requests\UpdateSubBrandRequest;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\Route;

Route::middleware('abilities:'.PortalTypeEnum::ADMIN->value.','.TokenTypeEnum::ACCESS_TOKEN->value)
    ->controller(SubBrandController::class)
    ->group(function () {
        Route::get('/excel/template', 'excelTemplate');
        Route::get('/excel/export', 'excelExport');
        Route::post('/excel/import', 'excelImport');

        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::match(['put', 'patch'], '/{id}', 'update');
        Route::delete('/{id}', 'destroy');

        Route::get('/trash', 'trash');
        Route::post('/bulk-delete', 'bulkDelete');
        Route::post('/trash/bulk-restore', 'bulkRestore');
        Route::delete('/trash/bulk-force-delete', 'bulkForceDelete');
        Route::post('/trash/{id}/restore', 'restore');
        Route::delete('/trash/{id}', 'forceDelete');
    });
