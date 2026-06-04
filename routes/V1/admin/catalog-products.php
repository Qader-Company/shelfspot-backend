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
    ->group(function () {
        Route::get('/excel/template', fn (string $company) => app(ProductController::class)->excelTemplate());
        Route::get('/excel/export', fn (string $company) => app(ProductController::class)->excelExport());
        Route::post('/excel/import', fn (ImportExcelRequest $request, string $company) => app(ProductController::class)->excelImport($request));
        Route::get('/', fn () => app(ProductController::class)->index());
        Route::get('/filter-options', fn (ProductFilterOptionsRequest $request, ProductFilterOptionsService $service) => app(ProductController::class)->filterOptions($request, $service));
        Route::post('/', fn (StoreProductRequest $request) => app(ProductController::class)->store($request));
        Route::get('/trash', fn (string $company) => app(ProductController::class)->trash());
        Route::post('/bulk-delete', fn (BulkActionRequest $request, string $company) => app(ProductController::class)->bulkDelete($request));
        Route::post('/trash/bulk-restore', fn (BulkActionRequest $request, string $company) => app(ProductController::class)->bulkRestore($request));
        Route::delete('/trash/bulk-force-delete', fn (BulkActionRequest $request, string $company) => app(ProductController::class)->bulkForceDelete($request));
        Route::post('/trash/{id}/restore', fn (string $company, string $id) => app(ProductController::class)->restore($id));
        Route::delete('/trash/{id}', fn (string $company, string $id) => app(ProductController::class)->forceDelete($id));
        Route::get('/{id}', fn (string $company, string $id) => app(ProductController::class)->show($id));
        Route::match(['put', 'patch'], '/{id}', fn (UpdateProductRequest $request, string $company, string $id) => app(ProductController::class)->update($request, $id));
        Route::delete('/{id}', fn (string $company, string $id) => app(ProductController::class)->destroy($id));
    });
