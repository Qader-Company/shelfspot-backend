<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Tasks\Presentation\Http\Controllers\CompanyTaskController;
use App\Modules\V1\Tasks\Presentation\Http\Controllers\TaskReviewMessageController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

Route::controller(CompanyTaskController::class)
    ->group(function () {
        Route::get('/', 'index')
            ->middleware('permission:'.CompanyPermissionEnum::VIEW_TASK->value);

        Route::post('/', 'store')
            ->middleware('permission:'.CompanyPermissionEnum::CREATE_TASK->value);

        Route::match(['put', 'patch'], '/{id}', 'update')
            ->middleware('permission:'.CompanyPermissionEnum::EDIT_TASK->value);

        Route::post('/{id}/pay', 'pay')
            ->middleware('permission:'.CompanyPermissionEnum::CREATE_TASK->value);

        Route::patch('/{id}/accept', 'accept')
            ->middleware('permission:'.CompanyPermissionEnum::VIEW_TASK->value);

        Route::post('/{id}/reject', 'reject')
            ->middleware('permission:'.CompanyPermissionEnum::VIEW_TASK->value);

        Route::get('/trash', 'trash')
            ->middleware('permission:'.CompanyPermissionEnum::VIEW_TASK->value);

        Route::post('/trash/{id}/restore', 'restore')
            ->middleware('permission:'.CompanyPermissionEnum::EDIT_TASK->value);

        Route::delete('/trash/{id}', 'purge')
            ->middleware('permission:'.CompanyPermissionEnum::DELETE_TASK->value);

        Route::get('/{id}', 'show')
            ->middleware('permission:'.CompanyPermissionEnum::VIEW_TASK->value);

        Route::delete('/{id}', 'destroy')
            ->middleware('permission:'.CompanyPermissionEnum::DELETE_TASK->value);

    });

Route::controller(TaskReviewMessageController::class)
    ->group(function () {
        Route::get('/{id}/review-messages', 'indexForCompany')
            ->middleware('permission:'.CompanyPermissionEnum::VIEW_TASK->value);

        Route::post('/{id}/review-messages', 'storeForCompany')
            ->middleware('permission:'.CompanyPermissionEnum::VIEW_TASK->value);

    });
