<?php

namespace App\Modules\V1\AccessControl\Domain\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = ['name', 'guard_name', 'portal', 'company_id'];
}
