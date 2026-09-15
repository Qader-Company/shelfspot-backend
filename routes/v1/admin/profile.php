<?php

use App\Modules\V1\Users\Presentation\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::controller(ProfileController::class)->group(function () {
    Route::get('/', 'show');
    Route::match(['put', 'patch'], '/', 'update');
});
