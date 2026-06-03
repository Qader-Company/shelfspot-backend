<?php

namespace App\Modules\V1\Users\Application\Services;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Users\Presentation\Http\Resources\AdminUserResource;
use App\Modules\V1\Users\Presentation\Http\Resources\CompanyUserResource;
use App\Modules\V1\Workers\Presentation\Http\Resources\WorkerResource;

class UserFormattingService
{
    public static function userFormat(User $user, PortalTypeEnum $userType)
    {
         return match ($userType){
            PortalTypeEnum::ADMIN => new AdminUserResource($user),
            PortalTypeEnum::WORKER => new WorkerResource($user),
            PortalTypeEnum::COMPANY => new CompanyUserResource($user),
        };
    }
}
