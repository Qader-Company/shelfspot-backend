<?php

use App\Facades\ApiResponse;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;

Route::get('industries', function () {
    return ApiResponse::success(CompanyIndustryEnum::getIndustries());
});

Route::get('transactions-types', function () {
    return ApiResponse::success(CompanyWalletTransactionTypeEnum::getTypes());
});

Route::get('task-statuses', function () {
    return ApiResponse::success(TaskStatusEnum::getStatuses());
});
