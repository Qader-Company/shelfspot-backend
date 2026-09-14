<?php

use App\Modules\V1\WorkersWallets\Presentation\Http\Controllers\WorkerWalletController;
use Illuminate\Support\Facades\Route;

Route::controller(WorkerWalletController::class)->group(function () {
    Route::get('/', 'show');
    Route::get('/transactions', 'transactions');
    Route::get('/withdrawals', 'withdrawals');
    Route::post('/withdrawals', 'storeWithdrawal');
    Route::get('/withdrawals/{withdrawal}', 'showWithdrawal');
});
