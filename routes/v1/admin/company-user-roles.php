<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\CompanyAdmins\Presentation\Http\Controllers\AdminCompanyUserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminCompanyUserManagementController::class, 'roles'])
    ->middleware('permission:'.AdminPermissionEnum::VIEW_COMPANY_USER->value);
