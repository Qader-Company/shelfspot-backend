<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Services\Presentation\Http\Controller\ServiceController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;


Route::controller(ServiceController::class)->group(function (){
    Route::get('/', 'index')->middleware('permission:'.CompanyPermissionEnum::VIEW_SERVICE->value);
    Route::get('/{id}', 'show')->middleware('permission:'.CompanyPermissionEnum::VIEW_SERVICE->value);
});
