<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\WorkersWallets\Presentation\Http\Controllers\AdminWithdrawalController;
use Illuminate\Support\Facades\Route;

Route::controller(AdminWithdrawalController::class)->group(function () {
    Route::get('/', 'index')
        ->middleware('permission:'.AdminPermissionEnum::VIEW_WITHDRAWAL->value);
    Route::get('/{withdrawal}', 'show')
        ->middleware('permission:'.AdminPermissionEnum::VIEW_WITHDRAWAL->value);
    Route::post('/{withdrawal}/approve', 'approve')
        ->middleware('permission:'.AdminPermissionEnum::PROCESS_WITHDRAWAL->value);
    Route::post('/{withdrawal}/reject', 'reject')
        ->middleware('permission:'.AdminPermissionEnum::PROCESS_WITHDRAWAL->value);
});
