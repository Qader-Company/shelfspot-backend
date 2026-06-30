<?php


use App\Facades\ApiResponse;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

Route::get('industries', function (){
    return ApiResponse::success(CompanyIndustryEnum::getIndustries());
});

Route::get('transactions-types', function (){
    return ApiResponse::success(CompanyWalletTransactionTypeEnum::getTypes());
});

Route::get('transactions-types', function (){
    return ApiResponse::success(PortalTypeEnum::getTypes());
});

Route::get('transactions-types', function (){
    return ApiResponse::success(OtpPurposeEnum::getTypes());
});




