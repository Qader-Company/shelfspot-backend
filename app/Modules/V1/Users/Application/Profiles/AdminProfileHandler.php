<?php

namespace App\Modules\V1\Users\Application\Profiles;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

class AdminProfileHandler extends AbstractProfileHandler
{
    protected function portal(): PortalTypeEnum
    {
        return PortalTypeEnum::ADMIN;
    }

    protected function relations(): array
    {
        return ['admin'];
    }
}
