<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\CompanyAdmins\Presentation\Http\Controllers\CompanyAdminManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/permissions', [CompanyAdminManagementController::class, 'groupedPermissions'])
    ->middleware('permission:'.CompanyPermissionEnum::VIEW_ROLE->value);
