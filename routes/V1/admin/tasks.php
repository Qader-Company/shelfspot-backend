<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Tasks\Presentation\Http\Controllers\AdminTaskController;
use App\Modules\V1\Tasks\Presentation\Http\Controllers\TaskReviewMessageController;
use Illuminate\Support\Facades\Route;

Route::controller(AdminTaskController::class)->group(function () {
        Route::get('/', 'index')
            ->middleware('permission:'.AdminPermissionEnum::VIEW_TASK->value);

        Route::get('/{id}/available-workers', 'availableWorkers')
            ->middleware('permission:'.AdminPermissionEnum::REASSIGN_TASK->value);

        Route::patch('/{id}/reassign', 'reassign')
            ->middleware('permission:'.AdminPermissionEnum::REASSIGN_TASK->value);

        Route::post('/{id}/reopen', 'reopen')
            ->middleware('permission:'.AdminPermissionEnum::REASSIGN_TASK->value);

        Route::post('/{id}/refund', 'refund')
            ->middleware('permission:'.AdminPermissionEnum::REASSIGN_TASK->value);

        Route::get('/{id}', 'show')
            ->middleware('permission:'.AdminPermissionEnum::VIEW_TASK->value);

    });

Route::controller(TaskReviewMessageController::class)->group(function () {
        Route::get('/{id}/review-messages', 'indexForAdmin')
            ->middleware('permission:'.AdminPermissionEnum::VIEW_TASK->value);
        Route::post('/{id}/review-messages', 'storeForAdmin')
            ->middleware('permission:'.AdminPermissionEnum::REASSIGN_TASK->value);
    });
