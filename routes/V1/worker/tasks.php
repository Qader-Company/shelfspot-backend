<?php

use App\Modules\V1\Tasks\Presentation\Http\Controllers\WorkerTaskController;
use Illuminate\Support\Facades\Route;

    Route::controller(WorkerTaskController::class)->group(function () {
        Route::get('/nearby', 'nearbyTasks');
        Route::get('/my', 'mine');
        Route::post('/{id}/start', 'start');
        Route::post('/{id}/execute', 'execute');
        Route::post('/{id}/services/{serviceId}/submission', 'submitService');
        Route::post('/{id}/complete', 'complete');
        Route::post('/{id}/cancel', 'cancel');
    });
