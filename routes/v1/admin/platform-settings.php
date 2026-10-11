<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\PlatformSettings\Presentation\Http\Controllers\PlatformSettingController;
use Illuminate\Support\Facades\Route;

Route::controller(PlatformSettingController::class)->group(function () {
    Route::get('/', 'show')
        ->middleware('permission:'.AdminPermissionEnum::VIEW_PLATFORM_SETTINGS->value);
    Route::match(['put', 'patch'], '/', 'update')
        ->middleware('permission:'.AdminPermissionEnum::EDIT_PLATFORM_SETTINGS->value);
});
