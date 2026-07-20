<?php

namespace App\Modules\V1\Users\Application\Profiles;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

class ProfileHandlerFactory
{
    public function for(User $user): ProfileHandler
    {
        return match ($user->type) {
            PortalTypeEnum::ADMIN => app(AdminProfileHandler::class),
            PortalTypeEnum::COMPANY => app(CompanyProfileHandler::class),
            PortalTypeEnum::WORKER => app(WorkerProfileHandler::class),
        };
    }
}
