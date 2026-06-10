<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Presentation\Http\Controllers\AdminWorkerController;
use Illuminate\Support\Facades\Route;

Route::middleware('abilities:'.PortalTypeEnum::ADMIN->value.','.TokenTypeEnum::ACCESS_TOKEN->value)
    ->controller(AdminWorkerController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{worker}', 'show');
        Route::match(['put', 'patch'], '/{worker}', 'update');
        Route::delete('/{worker}', 'destroy');
    });
