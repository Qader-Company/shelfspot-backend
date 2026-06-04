<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\Shared\Presentation\Http\Requests\BulkActionRequest;
use App\Modules\Shared\Presentation\Http\Requests\ImportExcelRequest;
use App\Modules\V1\SubCategories\Presentation\Http\Controller\SubCategoryController;
use App\Modules\V1\SubCategories\Presentation\Http\Requests\StoreSubCategoryRequest;
use App\Modules\V1\SubCategories\Presentation\Http\Requests\UpdateSubCategoryRequest;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\Route;

Route::middleware('abilities:'.PortalTypeEnum::ADMIN->value.','.TokenTypeEnum::ACCESS_TOKEN->value)
    ->group(function () {
        Route::get('/excel/template', fn (string $company) => app(SubCategoryController::class)->excelTemplate());
        Route::get('/excel/export', fn (string $company) => app(SubCategoryController::class)->excelExport());
        Route::post('/excel/import', fn (ImportExcelRequest $request, string $company) => app(SubCategoryController::class)->excelImport($request));
        Route::get('/', fn () => app(SubCategoryController::class)->index());
        Route::post('/', fn (StoreSubCategoryRequest $request) => app(SubCategoryController::class)->store($request));
        Route::get('/trash', fn (string $company) => app(SubCategoryController::class)->trash());
        Route::post('/bulk-delete', fn (BulkActionRequest $request, string $company) => app(SubCategoryController::class)->bulkDelete($request));
        Route::post('/trash/bulk-restore', fn (BulkActionRequest $request, string $company) => app(SubCategoryController::class)->bulkRestore($request));
        Route::delete('/trash/bulk-force-delete', fn (BulkActionRequest $request, string $company) => app(SubCategoryController::class)->bulkForceDelete($request));
        Route::post('/trash/{id}/restore', fn (string $company, string $id) => app(SubCategoryController::class)->restore($id));
        Route::delete('/trash/{id}', fn (string $company, string $id) => app(SubCategoryController::class)->forceDelete($id));
        Route::get('/{id}', fn (string $company, string $id) => app(SubCategoryController::class)->show($id));
        Route::match(['put', 'patch'], '/{id}', fn (UpdateSubCategoryRequest $request, string $company, string $id) => app(SubCategoryController::class)->update($request, $id));
        Route::delete('/{id}', fn (string $company, string $id) => app(SubCategoryController::class)->destroy($id));
    });
