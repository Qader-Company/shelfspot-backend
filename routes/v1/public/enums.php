<?php

use App\Facades\ApiResponse;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WithdrawalMethodEnum;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WithdrawalStatusEnum;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WorkerWalletTransactionTypeEnum;

Route::get('industries', function () {
    return ApiResponse::success(CompanyIndustryEnum::getIndustries());
});

Route::get('transactions-types', function () {
    return ApiResponse::success(CompanyWalletTransactionTypeEnum::getTypes());
});

Route::get('task-statuses', function () {
    return ApiResponse::success(TaskStatusEnum::getStatuses());
});

Route::get('withdrawal-methods', function () {
    return ApiResponse::success(WithdrawalMethodEnum::getMethods());
});

Route::get('withdrawal-statuses', function () {
    return ApiResponse::success(WithdrawalStatusEnum::getStatuses());
});

Route::get('worker-wallet-transaction-types', function () {
    return ApiResponse::success(WorkerWalletTransactionTypeEnum::getTypes());
});
