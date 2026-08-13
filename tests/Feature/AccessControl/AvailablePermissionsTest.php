<?php

namespace Tests\Feature\AccessControl;

use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\AccessControl\Presentation\Http\Resources\RoleResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailablePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_resource_separates_assigned_and_available_permissions(): void
    {
        PermissionCatalog::sync(PermissionCatalog::ADMIN_PORTAL);
        $role = Role::query()->create([
            'name' => 'service_viewer',
            'guard_name' => 'web',
            'portal' => PermissionCatalog::ADMIN_PORTAL,
            'company_id' => null,
        ]);
        $role->givePermissionTo(AdminPermissionEnum::VIEW_SERVICE->value);

        $loadedRole = app(AccessControlRepositoryInterface::class)
            ->roles(PermissionCatalog::ADMIN_PORTAL)
            ->firstWhere('id', $role->id);
        $payload = (new RoleResource($loadedRole))->resolve(request());

        $this->assertSame(
            [AdminPermissionEnum::VIEW_SERVICE->value],
            collect($payload['permissions'])->pluck('name')->all(),
        );
        $this->assertNotContains(
            AdminPermissionEnum::VIEW_SERVICE->value,
            collect($payload['available_permissions'])->pluck('name')->all(),
        );
        $this->assertCount(count(AdminPermissionEnum::cases()) - 1, $payload['available_permissions']);
    }
}
