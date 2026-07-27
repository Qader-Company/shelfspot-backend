<?php

namespace App\Modules\V1\Users\Application\Profiles;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

class CompanyProfileHandler extends AbstractProfileHandler
{
    protected function portal(): PortalTypeEnum
    {
        return PortalTypeEnum::COMPANY;
    }

    protected function relations(): array
    {
        return ['companyUser.company'];
    }
}
