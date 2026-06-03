<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Categories\Presentation\Http\Controller\CategoryController;
use App\Modules\V1\Categories\Presentation\Http\Requests\StoreCategoryRequest;
use App\Modules\V1\Categories\Presentation\Http\Requests\UpdateCategoryRequest;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\Route;

Route::middleware('abilities:'.PortalTypeEnum::ADMIN->value.','.TokenTypeEnum::ACCESS_TOKEN->value)
    ->group(function () {
        Route::get('/', fn () => app(CategoryController::class)->index());
        Route::post('/', fn (StoreCategoryRequest $request) => app(CategoryController::class)->store($request));
        Route::get('/{id}', fn (string $company, string $id) => app(CategoryController::class)->show($id));
        Route::match(['put', 'patch'], '/{id}', fn (UpdateCategoryRequest $request, string $company, string $id) => app(CategoryController::class)->update($request, $id));
        Route::delete('/{id}', fn (string $company, string $id) => app(CategoryController::class)->destroy($id));
    });
