<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\Shared\Presentation\Http\Requests\BulkActionRequest;
use App\Modules\Shared\Presentation\Http\Requests\ImportExcelRequest;
use App\Modules\V1\Products\Presentation\Http\Controller\ProductController;
use App\Modules\V1\Products\Presentation\Http\Requests\ProductFilterOptionsRequest;
use App\Modules\V1\Products\Presentation\Http\Requests\StoreProductRequest;
use App\Modules\V1\Products\Presentation\Http\Requests\UpdateProductRequest;
use App\Modules\V1\Products\Application\Services\ProductFilterOptionsService;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\Route;

Route::middleware('abilities:'.PortalTypeEnum::ADMIN->value.','.TokenTypeEnum::ACCESS_TOKEN->value)
    ->controller(ProductController::class)
    ->group(function () {
        Route::get('/filter-options', 'filterOptions');

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
