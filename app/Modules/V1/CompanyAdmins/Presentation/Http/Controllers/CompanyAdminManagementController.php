<?php

namespace App\Modules\V1\CompanyAdmins\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use App\Modules\V1\AccessControl\Domain\Repositories\ManagedAdminRepositoryInterface;
use App\Modules\V1\AccessControl\Presentation\Http\Controllers\AccessControlController;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\StoreRoleRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\UpdateRoleRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Resources\ManagedAdminResource;
use App\Modules\V1\CompanyAdmins\Presentation\Http\Requests\StoreCompanyAdminRequest;
use App\Modules\V1\CompanyAdmins\Presentation\Http\Requests\UpdateCompanyAdminRequest;
use App\Modules\V1\Users\Domain\Models\User;

class CompanyAdminManagementController extends AccessControlController
{
    private const PORTAL = PermissionCatalog::COMPANY_PORTAL;

    public function __construct(
        AccessControlRepositoryInterface $accessControlRepository,
        private readonly ManagedAdminRepositoryInterface $managedAdminRepository,
        private readonly TenantContextInterface $tenantContext
    ) {
        parent::__construct($accessControlRepository);
    }


    public function permissions()
    {
        return $this->listPermissions(
            self::PORTAL,
            $this->companyId()
        );
    }

    public function roles()
    {
        return $this->listRoles(
            self::PORTAL,
            $this->companyId()
        );
    }

    public function storeRole(StoreRoleRequest $request)
    {
        return $this->createRole(
            $request,
            self::PORTAL,
            $this->companyId()
        );
    }

    public function updateRole(UpdateRoleRequest $request, int $roleId)
    {
        return $this->modifyRole(
            $request,
            self::PORTAL,
            $roleId,
            $this->companyId()
        );
    }

    public function destroyRole(int $roleId)
    {
        return $this->deleteRole(
            self::PORTAL,
            $roleId,
            $this->companyId()
        );
    }

    public function admins()
    {
        return ApiResponse::success(
            ManagedAdminResource::collection(
                $this->managedAdminRepository->companyAdmins($this->companyId())
            )
        );
    }

    public function storeAdmin(StoreCompanyAdminRequest $request)
    {
        return ApiResponse::created(
            new ManagedAdminResource(
                $this->managedAdminRepository->createCompanyAdmin(
                    $this->companyId(),
                    $request->validated()
                )
            )
        );
    }

    public function updateAdmin(UpdateCompanyAdminRequest $request, User $user)
    {
        return ApiResponse::updated(
            new ManagedAdminResource(
                $this->managedAdminRepository->updateCompanyAdmin(
                    $this->companyId(),
                    $user,
                    $request->validated()
                )
            )
        );
    }

    public function destroyAdmin(User $user)
    {
        $this->managedAdminRepository->deleteCompanyAdmin(
            $this->companyId(),
            $user
        );

        return ApiResponse::deleted();
    }

    private function companyId(): int
    {
        return $this->tenantContext->getCompanyId();
    }
}
