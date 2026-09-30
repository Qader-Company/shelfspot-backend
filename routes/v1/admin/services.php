<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Services\Presentation\Http\Controller\ServiceController;

Route::controller(ServiceController::class)->group(function () {
    Route::get('/', 'index')
        ->middleware('permission:'.AdminPermissionEnum::VIEW_SERVICE->value);
    Route::get('/{id}', 'show')
        ->middleware('permission:'.AdminPermissionEnum::VIEW_SERVICE->value);
    Route::match(['put', 'patch'], '/{id}', 'update')
        ->middleware('permission:'.AdminPermissionEnum::EDIT_SERVICE->value);
});
