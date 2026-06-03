<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Companies\Presentation\Http\Companies\CompanyController;
use App\Modules\V1\Services\Presentation\Http\Controller\ServiceController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

Route::middleware('abilities:'. PortalTypeEnum::ADMIN->value .','. TokenTypeEnum::ACCESS_TOKEN->value)
    ->controller(CompanyController::class)->group(function (){
        Route::get('/', 'index');
        Route::post('/', 'create');
        Route::match(['put', 'patch'], '/{company}', 'update');
        Route::get('/{company}', 'show');
        Route::delete('/{company}', 'destroy');
    });
