<?php

namespace Database\Seeders;

use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use Illuminate\Database\Seeder;

class AccessControlPermissionSeeder extends Seeder
{
    public function run(): void
    {
        PermissionCatalog::sync(PermissionCatalog::ADMIN_PORTAL);

        PermissionCatalog::sync(PermissionCatalog::COMPANY_PORTAL);
    }
}
