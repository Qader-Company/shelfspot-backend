<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Brands\Presentation\Http\Controller\BrandController;
use App\Modules\V1\CompaniesWallets\Presentation\Http\Controllers\CompanyWalletController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

Route::middleware('abilities:'. PortalTypeEnum::COMPANY->value .','. TokenTypeEnum::ACCESS_TOKEN->value)
    ->controller(CompanyWalletController::class)->group(function (){
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/recharge', 'recharge');
    });
