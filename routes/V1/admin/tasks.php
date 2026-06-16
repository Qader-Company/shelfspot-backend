<?php

use App\Modules\V1\Tasks\Presentation\Http\Controllers\AdminTaskController;
use Illuminate\Support\Facades\Route;

Route::controller(AdminTaskController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}/available-workers', 'availableWorkers');
        Route::post('/{id}/reassign', 'reassign');
        Route::get('/{id}', 'show');
    });
