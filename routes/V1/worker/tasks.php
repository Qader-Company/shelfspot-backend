<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Tasks\Presentation\Http\Controllers\WorkerTaskController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Presentation\Http\Controllers\WorkerAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware('abilities:'.PortalTypeEnum::WORKER->value.','.TokenTypeEnum::ACCESS_TOKEN->value)
    ->group(function () {
        Route::controller(WorkerAccountController::class)->group(function () {
            Route::get('/nearby', 'nearbyTasks');
        });

        Route::controller(WorkerTaskController::class)->group(function () {
            Route::post('/{id}/accept', 'accept');
            Route::post('/{id}/start', 'start');
        });
    });
