<?php

use App\Modules\V1\Coupons\Presentation\Http\Controllers\WalletCouponController;

Route::controller(WalletCouponController::class)->group(function (){
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::match(['put', 'patch'], '/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });
