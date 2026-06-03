<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Brands\Presentation\Http\Controller\BrandController;
use App\Modules\V1\Brands\Presentation\Http\Requests\StoreBrandRequest;
use App\Modules\V1\Brands\Presentation\Http\Requests\UpdateBrandRequest;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\Route;

Route::middleware('abilities:'.PortalTypeEnum::ADMIN->value.','.TokenTypeEnum::ACCESS_TOKEN->value)
    ->group(function () {
        Route::get('/', fn () => app(BrandController::class)->index());
        Route::post('/', fn (StoreBrandRequest $request) => app(BrandController::class)->store($request));
        Route::get('/{id}', fn (string $company, string $id) => app(BrandController::class)->show($id));
        Route::match(['put', 'patch'], '/{id}', fn (UpdateBrandRequest $request, string $company, string $id) => app(BrandController::class)->update($request, $id));
        Route::delete('/{id}', fn (string $company, string $id) => app(BrandController::class)->destroy($id));
    });
