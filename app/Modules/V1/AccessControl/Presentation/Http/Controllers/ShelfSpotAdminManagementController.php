<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use App\Modules\V1\AccessControl\Domain\Repositories\ManagedAdminRepositoryInterface;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\StoreRoleRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\StoreShelfSpotAdminRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\UpdateRoleRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\UpdateShelfSpotAdminRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Resources\ManagedAdminResource;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShelfSpotAdminManagementController extends AccessControlController
{
    use Filterable;

    private const PORTAL = PermissionCatalog::ADMIN_PORTAL;

    public function __construct(
        AccessControlRepositoryInterface $accessControlRepository,
        private readonly ManagedAdminRepositoryInterface $managedAdminRepository
    ) {
        parent::__construct($accessControlRepository);
    }

    public function permissions()
    {
        return $this->listPermissions(self::PORTAL);
    }
    public function roles()
    {
        return $this->listRoles(self::PORTAL);
    }
    public function storeRole(StoreRoleRequest $request)
    {
        return $this->createRole($request, self::PORTAL);
    }

    public function updateRole(UpdateRoleRequest $request, int $roleId)
    {
        return $this->modifyRole($request, self::PORTAL, $roleId);
    }

    public function destroyRole(int $roleId)
    {
        return $this->deleteRole(self::PORTAL, $roleId);
    }


    public function admins(Request $request)
    {
        return ApiResponse::success(
            ManagedAdminResource::collection(
                $this->managedAdminRepository->shelfSpotAdmins(
                    $this->acceptedFilters($request, ['is_active', 'active', 'role', 'search'])
                )
            )
        );
    }

    public function storeAdmin(StoreShelfSpotAdminRequest $request)
    {
        return ApiResponse::created(
            new ManagedAdminResource(
                $this->managedAdminRepository->createShelfSpotAdmin($request->validated())
            )
        );
    }

    public function updateAdmin(UpdateShelfSpotAdminRequest $request, User $user)
    {
        return ApiResponse::updated(
            new ManagedAdminResource(
                $this->managedAdminRepository->updateShelfSpotAdmin($user, $request->validated())
            )
        );
    }

    public function destroyAdmin(User $user): JsonResponse
    {
        $this->managedAdminRepository->deleteShelfSpotAdmin($user);

        return ApiResponse::deleted();
    }
}
