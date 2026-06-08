<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Companies\Presentation\Http\Companies\CompanyController;
use App\Modules\V1\Services\Presentation\Http\Controller\ServiceController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

Route::middleware('abilities:'. PortalTypeEnum::ADMIN->value .','. TokenTypeEnum::ACCESS_TOKEN->value)
    ->controller(CompanyController::class)->group(function (){
        Route::get('/', 'index');
        Route::get('/{company}', 'show');
        Route::post('/', 'create');
        Route::match(['put', 'patch'], '/{company}', 'update');
        Route::post('/bulk-delete', 'bulkDelete');
        Route::delete('/{company}', 'destroy');
        Route::prefix('trash')->group(function (){
            Route::get('', 'trash');
            Route::post('/bulk-restore', 'bulkRestore');
            Route::delete('/bulk-force-delete', 'bulkForceDelete');
            Route::post('/{id}/restore', 'restore');
            Route::delete('/{id}', 'forceDelete');
        });
    });
