<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Coupons\Presentation\Http\Controllers\WalletCouponController;

Route::middleware('abilities:'. PortalTypeEnum::ADMIN->value .','. TokenTypeEnum::ACCESS_TOKEN->value)
    ->controller(WalletCouponController::class)->group(function (){
        Route::get('/', 'index')->middleware('permission:'.AdminPermissionEnum::VIEW_WALLET_COUPON->value);
        Route::post('/', 'store')->middleware('permission:'.AdminPermissionEnum::CREATE_WALLET_COUPON->value);
        Route::get('/{id}', 'show')->middleware('permission:'.AdminPermissionEnum::VIEW_WALLET_COUPON->value);
        Route::match(['put', 'patch'], '/{id}', 'update')->middleware('permission:'.AdminPermissionEnum::EDIT_WALLET_COUPON->value);
        Route::delete('/{id}', 'destroy')->middleware('permission:'.AdminPermissionEnum::DELETE_WALLET_COUPON->value);
    });
