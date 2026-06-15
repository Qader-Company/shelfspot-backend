<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Tasks\Presentation\Http\Controllers\AdminTaskController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\Route;

Route::middleware('abilities:'.PortalTypeEnum::ADMIN->value.','.TokenTypeEnum::ACCESS_TOKEN->value)
    ->controller(AdminTaskController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}/available-workers', 'availableWorkers');
        Route::post('/{id}/reassign', 'reassign');
        Route::get('/{id}', 'show');
    });
