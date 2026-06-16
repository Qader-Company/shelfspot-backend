<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Presentation\Http\Controllers\AdminWorkerController;
use Illuminate\Support\Facades\Route;

Route::controller(AdminWorkerController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:'.AdminPermissionEnum::VIEW_WORKER->value);
        Route::post('/', 'store')->middleware('permission:'.AdminPermissionEnum::CREATE_WORKER->value);
        Route::get('/{worker}', 'show')->middleware('permission:'.AdminPermissionEnum::VIEW_WORKER->value);
        Route::match(['put', 'patch'], '/{worker}', 'update')->middleware('permission:'.AdminPermissionEnum::EDIT_WORKER->value);
        Route::delete('/{worker}', 'destroy')->middleware('permission:'.AdminPermissionEnum::DELETE_WORKER->value);
        Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:'.AdminPermissionEnum::DELETE_WORKER->value);

        Route::prefix('trash')->group(function () {
            Route::get('', 'trash')->middleware('permission:'.AdminPermissionEnum::VIEW_WORKER->value);
            Route::post('/bulk-restore', 'bulkRestore')->middleware('permission:'.AdminPermissionEnum::EDIT_WORKER->value);
            Route::delete('/bulk-force-delete', 'bulkForceDelete')->middleware('permission:'.AdminPermissionEnum::DELETE_WORKER->value);
            Route::post('/{id}/restore', 'restore')->middleware('permission:'.AdminPermissionEnum::EDIT_WORKER->value);
            Route::delete('/{id}', 'forceDelete')->middleware('permission:'.AdminPermissionEnum::DELETE_WORKER->value);
        });
    });
