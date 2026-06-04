<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Brands\Presentation\Http\Controller\BrandController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

Route::middleware('abilities:'. PortalTypeEnum::COMPANY->value .','. TokenTypeEnum::ACCESS_TOKEN->value)
    ->controller(BrandController::class)->group(function (){
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/excel/template', 'excelTemplate');
        Route::get('/excel/export', 'excelExport');
        Route::post('/excel/import', 'excelImport');
        Route::get('/{id}', 'show');
        Route::match(['put', 'patch'],'/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });
