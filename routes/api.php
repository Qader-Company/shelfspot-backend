<?php

use App\Modules\V1\PlatformSettings\Presentation\Http\Controllers\PlatformSettingController;
use Illuminate\Support\Facades\Route;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');

Route::get('admin/platform-settings/', [PlatformSettingController::class, 'show']);
