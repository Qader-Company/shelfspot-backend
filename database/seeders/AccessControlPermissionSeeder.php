<?php

namespace Database\Seeders;

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;
use Illuminate\Database\Seeder;

class AccessControlPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // New permissions are persisted, and existing protected full-access
        // roles receive any permissions added after their original seed.
        $provisioner = app(FullAccessRoleProvisioner::class);
        $provisioner->syncFullAccessRoles();

        // Backfill the protected owner role for companies created before the
        // access-control seeder was introduced.
        CompanyUser::query()
            ->with('user')
            ->where('is_owner', true)
            ->each(fn (CompanyUser $companyUser) => $provisioner->assignCompanyOwnerRole(
                $companyUser->user,
                $companyUser->company_id,
            ));
    }
}
