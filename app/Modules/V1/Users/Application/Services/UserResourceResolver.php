<?php

namespace App\Modules\V1\Users\Application\Services;

use App\Modules\V1\AccessControl\Application\Services\UserPermissionLoader;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Users\Presentation\Http\Resources\AdminUserResource;
use App\Modules\V1\Users\Presentation\Http\Resources\CompanyUserResource;
use App\Modules\V1\Workers\Presentation\Http\Resources\WorkerResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResourceResolver
{
    public static function resolve(User $user, PortalTypeEnum $userType): JsonResource
    {
        $user->loadMissing(match ($userType) {
            PortalTypeEnum::ADMIN => ['admin'],
            PortalTypeEnum::WORKER => ['worker'],
            PortalTypeEnum::COMPANY => ['companyUser.company'],
        });

        UserPermissionLoader::load($user, $userType);

        return match ($userType) {
            PortalTypeEnum::ADMIN => new AdminUserResource($user),
            PortalTypeEnum::WORKER => new WorkerResource($user),
            PortalTypeEnum::COMPANY => new CompanyUserResource($user),
        };
    }
}
