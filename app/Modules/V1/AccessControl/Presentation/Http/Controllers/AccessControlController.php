<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\StoreRoleRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\UpdateRoleRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Resources\PermissionResource;
use App\Modules\V1\AccessControl\Presentation\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;

abstract class AccessControlController extends Controller
{
    public function __construct(protected readonly AccessControlRepositoryInterface $accessControlRepository) {}

    protected function listPermissions(string $portal, ?int $companyId = null): JsonResponse
    {
        return ApiResponse::success(PermissionResource::collection($this->accessControlRepository->permissions($portal, $companyId)));
    }

    protected function listRoles(string $portal, ?int $companyId = null): JsonResponse
    {
        return ApiResponse::success(RoleResource::collection($this->accessControlRepository->roles($portal, $companyId)));
    }

    protected function createRole(StoreRoleRequest $request, string $portal, ?int $companyId = null): JsonResponse
    {
        return ApiResponse::created(new RoleResource($this->accessControlRepository->createRole($portal, $companyId, $request->validated())));
    }

    protected function modifyRole(UpdateRoleRequest $request, string $portal, int $roleId, ?int $companyId = null): JsonResponse
    {
        return ApiResponse::updated(new RoleResource($this->accessControlRepository->updateRole($portal, $companyId, $roleId, $request->validated())));
    }

    protected function deleteRole(string $portal, int $roleId, ?int $companyId = null): JsonResponse
    {
        $this->accessControlRepository->deleteRole($portal, $companyId, $roleId);
        return ApiResponse::deleted();
    }
}
