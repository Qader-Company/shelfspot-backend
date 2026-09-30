<?php

use App\Modules\V1\Workers\Presentation\Http\Controllers\WorkerAccountController;
use Illuminate\Support\Facades\Route;

Route::controller(WorkerAccountController::class)->group(function () {
    Route::get('/profile', 'profile');
    Route::post('/profile', 'updateProfile');
    Route::delete('/profile', 'deleteAccount');
    Route::patch('/location', 'updateLocation');
});
