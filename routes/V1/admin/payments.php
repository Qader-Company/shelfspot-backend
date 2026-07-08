<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Payments\Presentation\Http\Controllers\AdminPaymentController;
use Illuminate\Support\Facades\Route;

Route::controller(AdminPaymentController::class)->group(function () {
    Route::get('/', 'index')
        ->middleware('permission:'.AdminPermissionEnum::VIEW_PAYMENT->value);

    Route::get('/{id}', 'show')
        ->middleware('permission:'.AdminPermissionEnum::VIEW_PAYMENT->value);
});
