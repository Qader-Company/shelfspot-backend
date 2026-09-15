<?php

use App\Modules\V1\Services\Presentation\Http\Controller\ServiceController;

Route::controller(ServiceController::class)->group(function (){
    Route::get('/', 'index');
    Route::get('/{id}', 'show');
});
