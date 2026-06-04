<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\Shared\Presentation\Http\Requests\BulkActionRequest;
use App\Modules\V1\Brands\Presentation\Http\Controller\BrandController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\Route;

Route::middleware('abilities:'.PortalTypeEnum::ADMIN->value.','.TokenTypeEnum::ACCESS_TOKEN->value)
    ->controller(BrandController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/excel/template', 'excelTemplate');
        Route::get('/excel/export', 'excelExport');
        Route::post('/excel/import', 'excelImport');
        Route::post('/', 'store');
        Route::get('/trash', fn (string $company) => app(BrandController::class)->trash());
        Route::post('/bulk-delete', fn (BulkActionRequest $request, string $company) => app(BrandController::class)->bulkDelete($request));
        Route::post('/trash/bulk-restore', fn (BulkActionRequest $request, string $company) => app(BrandController::class)->bulkRestore($request));
        Route::delete('/trash/bulk-force-delete', fn (BulkActionRequest $request, string $company) => app(BrandController::class)->bulkForceDelete($request));
        Route::post('/trash/{id}/restore', fn (string $company, string $id) => app(BrandController::class)->restore($id));
        Route::delete('/trash/{id}', fn (string $company, string $id) => app(BrandController::class)->forceDelete($id));
        Route::get('/{id}', 'show');
        Route::match(['put', 'patch'], '/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });
