<?php


use App\Facades\ApiResponse;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;

Route::get('industries', function (){
    return ApiResponse::success(CompanyIndustryEnum::getIndustries());
});

Route::get('transactions-types', function (){
    return ApiResponse::success(CompanyWalletTransactionTypeEnum::getTypes());
});




