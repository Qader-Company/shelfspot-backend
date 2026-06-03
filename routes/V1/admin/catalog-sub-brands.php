<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\Shared\Presentation\Http\Requests\ImportExcelRequest;
use App\Modules\V1\SubBrands\Presentation\Http\Controller\SubBrandController;
use App\Modules\V1\SubBrands\Presentation\Http\Requests\StoreSubBrandRequest;
use App\Modules\V1\SubBrands\Presentation\Http\Requests\UpdateSubBrandRequest;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\Route;

Route::middleware('abilities:'.PortalTypeEnum::ADMIN->value.','.TokenTypeEnum::ACCESS_TOKEN->value)
    ->group(function () {
        Route::get('/excel/template', fn (string $company) => app(SubBrandController::class)->excelTemplate());
        Route::get('/excel/export', fn (string $company) => app(SubBrandController::class)->excelExport());
        Route::post('/excel/import', fn (ImportExcelRequest $request, string $company) => app(SubBrandController::class)->excelImport($request));
        Route::get('/', fn () => app(SubBrandController::class)->index());
        Route::post('/', fn (StoreSubBrandRequest $request) => app(SubBrandController::class)->store($request));
        Route::get('/{id}', fn (string $company, string $id) => app(SubBrandController::class)->show($id));
        Route::match(['put', 'patch'], '/{id}', fn (UpdateSubBrandRequest $request, string $company, string $id) => app(SubBrandController::class)->update($request, $id));
        Route::delete('/{id}', fn (string $company, string $id) => app(SubBrandController::class)->destroy($id));
    });
