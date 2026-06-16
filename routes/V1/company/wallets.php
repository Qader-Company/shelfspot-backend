<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\CompaniesWallets\Presentation\Http\Controllers\CompanyWalletController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

Route::middleware('abilities:'. PortalTypeEnum::COMPANY->value .','. TokenTypeEnum::ACCESS_TOKEN->value)
    ->controller(CompanyWalletController::class)->group(function (){
        Route::get('/', 'index')->middleware('permission:'.CompanyPermissionEnum::VIEW_WALLET->value);
        Route::post('/recharge', 'recharge')->middleware('permission:'.CompanyPermissionEnum::RECHARGE_WALLET->value);
        Route::post('/coupons/redeem', 'redeemCoupon')->middleware('permission:'.CompanyPermissionEnum::RECHARGE_WALLET->value);
        Route::get('/{id}', 'show')->middleware('permission:'.CompanyPermissionEnum::VIEW_WALLET->value);
    });
