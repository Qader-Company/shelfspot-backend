<?php

namespace App\Modules\V1\AccessControl\Domain\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{

    protected $fillable = ['name', 'guard_name', 'portal', 'company_id'];
}
