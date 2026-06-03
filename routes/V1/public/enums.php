<?php


use App\Facades\ApiResponse;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;

Route::get('industries', function (){
    return ApiResponse::success(CompanyIndustryEnum::getIndustries());
});



