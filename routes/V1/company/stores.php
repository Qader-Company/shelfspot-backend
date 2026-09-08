<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\Stores\Presentation\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::controller(StoreController::class)->group(function () {
    Route::prefix('trash')->group(function () {
        Route::get('', 'trash')->middleware('permission:'.CompanyPermissionEnum::VIEW_STORE->value);
        Route::post('/bulk-restore', 'bulkRestore')->middleware('permission:'.CompanyPermissionEnum::EDIT_STORE->value);
        Route::delete('/bulk-force-delete', 'bulkForceDelete')->middleware('permission:'.CompanyPermissionEnum::DELETE_STORE->value);
        Route::post('/{id}/restore', 'restore')->middleware('permission:'.CompanyPermissionEnum::EDIT_STORE->value);
        Route::delete('/{id}', 'forceDelete')->middleware('permission:'.CompanyPermissionEnum::DELETE_STORE->value);
    });

    Route::get('/options', 'options')->middleware(
        'permission:'.CompanyPermissionEnum::VIEW_STORE->value.'|'.CompanyPermissionEnum::CREATE_TASK->value
    );
    Route::get('/', 'index')->middleware('permission:'.CompanyPermissionEnum::VIEW_STORE->value);
    Route::post('/', 'store')->middleware('permission:'.CompanyPermissionEnum::CREATE_STORE->value);
    Route::post('/bulk-delete', 'bulkDelete')->middleware('permission:'.CompanyPermissionEnum::DELETE_STORE->value);
    Route::get('/{id}', 'show')->middleware('permission:'.CompanyPermissionEnum::VIEW_STORE->value);
    Route::match(['put', 'patch'], '/{id}', 'update')->middleware('permission:'.CompanyPermissionEnum::EDIT_STORE->value);
    Route::delete('/{id}', 'destroy')->middleware('permission:'.CompanyPermissionEnum::DELETE_STORE->value);
});
