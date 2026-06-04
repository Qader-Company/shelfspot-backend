<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\Shared\Presentation\Http\Requests\BulkActionRequest;
use App\Modules\Shared\Presentation\Http\Requests\ImportExcelRequest;
use App\Modules\V1\Categories\Presentation\Http\Controller\CategoryController;
use App\Modules\V1\Categories\Presentation\Http\Requests\StoreCategoryRequest;
use App\Modules\V1\Categories\Presentation\Http\Requests\UpdateCategoryRequest;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\Route;

Route::middleware('abilities:'.PortalTypeEnum::ADMIN->value.','.TokenTypeEnum::ACCESS_TOKEN->value)
    ->group(function () {
        Route::get('/excel/template', fn (string $company) => app(CategoryController::class)->excelTemplate());
        Route::get('/excel/export', fn (string $company) => app(CategoryController::class)->excelExport());
        Route::post('/excel/import', fn (ImportExcelRequest $request, string $company) => app(CategoryController::class)->excelImport($request));
        Route::get('/', fn () => app(CategoryController::class)->index());
        Route::post('/', fn (StoreCategoryRequest $request) => app(CategoryController::class)->store($request));
        Route::get('/trash', fn (string $company) => app(CategoryController::class)->trash());
        Route::post('/bulk-delete', fn (BulkActionRequest $request, string $company) => app(CategoryController::class)->bulkDelete($request));
        Route::post('/trash/bulk-restore', fn (BulkActionRequest $request, string $company) => app(CategoryController::class)->bulkRestore($request));
        Route::delete('/trash/bulk-force-delete', fn (BulkActionRequest $request, string $company) => app(CategoryController::class)->bulkForceDelete($request));
        Route::post('/trash/{id}/restore', fn (string $company, string $id) => app(CategoryController::class)->restore($id));
        Route::delete('/trash/{id}', fn (string $company, string $id) => app(CategoryController::class)->forceDelete($id));
        Route::get('/{id}', fn (string $company, string $id) => app(CategoryController::class)->show($id));
        Route::match(['put', 'patch'], '/{id}', fn (UpdateCategoryRequest $request, string $company, string $id) => app(CategoryController::class)->update($request, $id));
        Route::delete('/{id}', fn (string $company, string $id) => app(CategoryController::class)->destroy($id));
    });
