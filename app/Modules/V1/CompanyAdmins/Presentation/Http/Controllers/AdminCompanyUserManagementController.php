<?php

namespace App\Modules\V1\CompanyAdmins\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use App\Modules\V1\AccessControl\Domain\Repositories\ManagedAdminRepositoryInterface;
use App\Modules\V1\AccessControl\Presentation\Http\Resources\RoleResource;
use App\Modules\V1\CompanyAdmins\Presentation\Http\Requests\AdminResetCompanyUserPasswordRequest;
use App\Modules\V1\CompanyAdmins\Presentation\Http\Requests\AdminUpdateCompanyUserRequest;
use App\Modules\V1\CompanyAdmins\Presentation\Http\Requests\StoreCompanyAdminRequest;
use App\Modules\V1\CompanyAdmins\Presentation\Http\Resources\AdminCompanyUserResource;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCompanyUserManagementController extends Controller
{
    use Filterable;

    public function __construct(
        private readonly ManagedAdminRepositoryInterface $managedAdminRepository,
        private readonly AccessControlRepositoryInterface $accessControlRepository,
        private readonly TenantContextInterface $tenantContext,
    ) {}

    public function index(Request $request, int $company): JsonResponse
    {
        $users = $this->managedAdminRepository->companyAdmins(
            $this->companyId(),
            $this->acceptedFilters($request, ['is_active', 'role', 'search']),
        );

        return ApiResponse::success(AdminCompanyUserResource::collection($users));
    }

    public function roles(int $company): JsonResponse
    {
        return ApiResponse::success(
            RoleResource::collection(
                $this->accessControlRepository->roles(
                    PermissionCatalog::COMPANY_PORTAL,
                    $this->companyId(),
                )
            )
        );
    }

    public function store(StoreCompanyAdminRequest $request, int $company): JsonResponse
    {
        $user = $this->managedAdminRepository->createCompanyAdmin(
            $this->companyId(),
            $request->validated(),
        );

        return ApiResponse::created(new AdminCompanyUserResource($user));
    }

    public function show(int $company, int $user): JsonResponse
    {
        return ApiResponse::success(
            new AdminCompanyUserResource($this->companyUser($user))
        );
    }

    public function update(AdminUpdateCompanyUserRequest $request, int $company, int $user): JsonResponse
    {
        $updatedUser = $this->managedAdminRepository->updateCompanyAdminAsShelfSpotAdmin(
            $this->companyId(),
            $this->companyUser($user),
            $request->validated(),
        );

        return ApiResponse::updated(new AdminCompanyUserResource($updatedUser));
    }

    public function resetPassword(AdminResetCompanyUserPasswordRequest $request, int $company, int $user): JsonResponse
    {
        $this->managedAdminRepository->resetCompanyAdminPassword(
            $this->companyId(),
            $this->companyUser($user),
            $request->validated('password'),
        );

        return ApiResponse::message(__('auth.password_reset_success'));
    }

    public function destroy(int $company, int $user): JsonResponse
    {
        $this->managedAdminRepository->deleteCompanyAdminAsShelfSpotAdmin(
            $this->companyId(),
            $this->companyUser($user),
        );

        return ApiResponse::deleted();
    }

    private function companyUser(int $userId): User
    {
        return $this->managedAdminRepository->findCompanyAdmin($this->companyId(), $userId);
    }

    private function companyId(): int
    {
        return $this->tenantContext->getCompanyId();
    }
}
