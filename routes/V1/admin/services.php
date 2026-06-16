<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Services\Presentation\Http\Controller\ServiceController;

Route::controller(ServiceController::class)->group(function (){
        Route::get('/', 'index')
            ->middleware('permission:'.AdminPermissionEnum::VIEW_SERVICE->value);
        Route::post('/', 'store')
            ->middleware('permission:'.AdminPermissionEnum::CREATE_SERVICE->value);
        Route::get('/{id}', 'show')
            ->middleware('permission:'.AdminPermissionEnum::VIEW_SERVICE->value);
        Route::match(['put', 'patch'],'/{id}', 'update')
            ->middleware('permission:'.AdminPermissionEnum::EDIT_SERVICE->value);
        Route::delete('/{id}', 'destroy')
            ->middleware('permission:'.AdminPermissionEnum::DELETE_SERVICE->value);
    });
