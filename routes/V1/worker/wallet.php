<?php

use App\Modules\V1\WorkersWallets\Presentation\Http\Controllers\WorkerWalletController;
use Illuminate\Support\Facades\Route;

Route::controller(WorkerWalletController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/withdrawals', 'storeWithdrawal');
});
