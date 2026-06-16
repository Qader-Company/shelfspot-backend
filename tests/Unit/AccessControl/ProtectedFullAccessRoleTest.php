<?php

namespace Tests\Unit\AccessControl;

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Infrastructure\Persistence\Repositories\EloquentAccessControlRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtectedFullAccessRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_role_cannot_be_updated(): void
    {
        $role = app(FullAccessRoleProvisioner::class)->ensureSuperAdminRole();

        $this->expectException(AuthorizationException::class);

        app(EloquentAccessControlRepository::class)->updateRole(
            PermissionCatalog::ADMIN_PORTAL,
            null,
            $role->id,
            ['name' => 'changed_super_admin']
        );
    }

    public function test_super_admin_role_cannot_be_deleted(): void
    {
        $role = app(FullAccessRoleProvisioner::class)->ensureSuperAdminRole();

        $this->expectException(AuthorizationException::class);

        app(EloquentAccessControlRepository::class)->deleteRole(
            PermissionCatalog::ADMIN_PORTAL,
            null,
            $role->id
        );
    }

    public function test_company_owner_role_cannot_be_updated(): void
    {
        $companyId = 10;
        $role = app(FullAccessRoleProvisioner::class)->ensureCompanyOwnerRole($companyId);

        $this->expectException(AuthorizationException::class);

        app(EloquentAccessControlRepository::class)->updateRole(
            PermissionCatalog::COMPANY_PORTAL,
            $companyId,
            $role->id,
            ['name' => 'changed_owner']
        );
    }

    public function test_company_owner_role_cannot_be_deleted(): void
    {
        $companyId = 10;
        $role = app(FullAccessRoleProvisioner::class)->ensureCompanyOwnerRole($companyId);

        $this->expectException(AuthorizationException::class);

        app(EloquentAccessControlRepository::class)->deleteRole(
            PermissionCatalog::COMPANY_PORTAL,
            $companyId,
            $role->id
        );
    }
}
