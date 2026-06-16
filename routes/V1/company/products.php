<?php

use App\Modules\V1\Authentication\Domain\ValueObjects\TokenTypeEnum;
use App\Modules\V1\Products\Presentation\Http\Controller\ProductController;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

Route::controller(ProductController::class)->group(function (){
        Route::get('/', 'index');
        Route::get('/filter-options', 'filterOptions');
        Route::post('/', 'store');
        Route::get('/excel/template', 'excelTemplate');
        Route::get('/excel/export', 'excelExport');
        Route::post('/excel/import', 'excelImport');
        Route::get('/trash', 'trash');
        Route::post('/bulk-delete', 'bulkDelete');
        Route::post('/trash/bulk-restore', 'bulkRestore');
        Route::delete('/trash/bulk-force-delete', 'bulkForceDelete');
        Route::post('/trash/{id}/restore', 'restore');
        Route::delete('/trash/{id}', 'forceDelete');
        Route::get('/{id}', 'show');
        Route::match(['put','patch'],'/{id}','update');
        Route::delete('/{id}','destroy');
    });
