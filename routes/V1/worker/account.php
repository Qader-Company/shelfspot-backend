<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Presentation\Http\Controllers\WorkerAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware('abilities:'.PortalTypeEnum::WORKER->value.','.TokenTypeEnum::ACCESS_TOKEN->value)
    ->controller(WorkerAccountController::class)
    ->group(function () {
        Route::get('/profile', 'profile');
        Route::match(['put', 'patch'], '/profile', 'updateProfile');
        Route::delete('/profile', 'deleteAccount');
        Route::patch('/location', 'updateLocation');
        Route::get('/tasks/nearby', 'nearbyTasks');
    });
