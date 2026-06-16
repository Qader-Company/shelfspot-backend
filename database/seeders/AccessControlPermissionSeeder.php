<?php

namespace Database\Seeders;

use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\Companies\Domain\Models\Company;
use Illuminate\Database\Seeder;

class AccessControlPermissionSeeder extends Seeder
{
    public function run(): void
    {
        PermissionCatalog::sync(PermissionCatalog::ADMIN_PORTAL);

        Company::query()->select('id')->chunkById(100, function ($companies) {
            foreach ($companies as $company) {
                PermissionCatalog::sync(PermissionCatalog::COMPANY_PORTAL, $company->id);
            }
        });
    }
}
