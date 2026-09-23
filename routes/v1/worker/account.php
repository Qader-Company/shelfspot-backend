<?php

use App\Modules\V1\Users\Presentation\Http\Controllers\ProfileController;
use App\Modules\V1\Workers\Presentation\Http\Controllers\WorkerAccountController;
use Illuminate\Support\Facades\Route;

Route::controller(WorkerAccountController::class)->group(function () {
    Route::get('/profile', 'profile');
    Route::match(['put', 'patch'], '/profile', 'updateProfile');
    Route::delete('/profile', 'deleteAccount');
    Route::patch('/location', 'updateLocation');
    Route::get('/tasks/nearby', 'nearbyTasks');
    Route::post('/device-tokens', [ProfileController::class, 'storeDeviceToken'])
        ->middleware('throttle:30,1');
    Route::delete('/device-tokens', [ProfileController::class, 'destroyDeviceToken'])
        ->middleware('throttle:30,1');
});
