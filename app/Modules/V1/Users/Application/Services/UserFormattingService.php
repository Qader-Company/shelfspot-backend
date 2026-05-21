<?php

namespace App\Modules\V1\Users\Application\Services;

use App\Modules\V1\Companies\Presentation\Http\Resourcses\CompanyUserResource;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Presentation\Http\Resourcses\WorkerResource;

class UserFormattingService
{
    public static function userFormat(User $user, PortalTypeEnum $userType)
    {
         return match ($userType){
            PortalTypeEnum::ADMIN => 'admin',
            PortalTypeEnum::WORKER => new WorkerResource($user),
            PortalTypeEnum::COMPANY => new CompanyUserResource($user),
        };
    }
}
