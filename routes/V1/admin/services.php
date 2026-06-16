<?php

use App\Modules\V1\Services\Presentation\Http\Controller\ServiceController;

Route::controller(ServiceController::class)->group(function (){
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::match(['put', 'patch'],'/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });
