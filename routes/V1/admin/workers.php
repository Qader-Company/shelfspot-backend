<?php

use App\Modules\V1\Workers\Presentation\Http\Controllers\AdminWorkerController;
use Illuminate\Support\Facades\Route;

Route::controller(AdminWorkerController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::post('/bulk-delete', 'bulkDelete');
        Route::prefix('trash')->group(function () {
            Route::get('', 'trash');
            Route::post('/bulk-restore', 'bulkRestore');
            Route::delete('/bulk-force-delete', 'bulkForceDelete');
            Route::post('/{id}/restore', 'restore');
            Route::delete('/{id}', 'forceDelete');
        });
        Route::get('/{worker}', 'show');
        Route::match(['put', 'patch'], '/{worker}', 'update');
        Route::delete('/{worker}', 'destroy');
    });
