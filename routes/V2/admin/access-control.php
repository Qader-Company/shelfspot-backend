<?php

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Admins\Presentation\Http\Controller\ShelfSpotAdminManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/permission-groups', [ShelfSpotAdminManagementController::class, 'groupedPermissions'])
    ->middleware('permission:'.AdminPermissionEnum::VIEW_ROLE->value);
