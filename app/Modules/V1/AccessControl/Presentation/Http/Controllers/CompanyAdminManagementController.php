<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use App\Modules\V1\AccessControl\Domain\Repositories\ManagedAdminRepositoryInterface;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\StoreCompanyAdminRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\StoreRoleRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\UpdateCompanyAdminRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\UpdateRoleRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Resources\ManagedAdminResource;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyAdminManagementController extends AccessControlController
{
    use Filterable;

    private const PORTAL = PermissionCatalog::COMPANY_PORTAL;

    public function __construct(
        AccessControlRepositoryInterface $accessControlRepository,
        private readonly ManagedAdminRepositoryInterface $managedAdminRepository,
        private readonly TenantContextInterface $tenantContext
    ) {
        parent::__construct($accessControlRepository);
    }

    public function permissions(): JsonResponse { return $this->listPermissions(self::PORTAL, $this->companyId()); }
    public function roles(): JsonResponse { return $this->listRoles(self::PORTAL, $this->companyId()); }
    public function storeRole(StoreRoleRequest $request): JsonResponse { return $this->createRole($request, self::PORTAL, $this->companyId()); }
    public function updateRole(UpdateRoleRequest $request, int $roleId): JsonResponse { return $this->modifyRole($request, self::PORTAL, $roleId, $this->companyId()); }
    public function destroyRole(int $roleId): JsonResponse { return $this->deleteRole(self::PORTAL, $roleId, $this->companyId()); }

    public function admins(Request $request)
    {
        return ApiResponse::success(
            ManagedAdminResource::collection(
                $this->managedAdminRepository->companyAdmins(
                    $this->companyId(),
                    $this->acceptedFilters($request, ['is_active', 'active', 'role', 'search'])
                )
            )
        );
    }

    public function storeAdmin(StoreCompanyAdminRequest $request): JsonResponse
    {
        return ApiResponse::created(new ManagedAdminResource($this->managedAdminRepository->createCompanyAdmin($this->companyId(), $request->validated())));
    }

    public function updateAdmin(UpdateCompanyAdminRequest $request, User $user): JsonResponse
    {
        return ApiResponse::updated(new ManagedAdminResource($this->managedAdminRepository->updateCompanyAdmin($this->companyId(), $user, $request->validated())));
    }

    public function destroyAdmin(User $user): JsonResponse
    {
        $this->managedAdminRepository->deleteCompanyAdmin($this->companyId(), $user);

        return ApiResponse::deleted();
    }

    private function companyId(): int
    {
        return $this->tenantContext->getCompanyId();
    }
}
