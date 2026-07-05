<?php

namespace App\Modules\Shared\Domain\Contracts;

use App\Modules\V1\Companies\Domain\Models\Company;

interface TenantContextInterface
{
    public function setCompany(string $id): bool;

    public function getCompany(): ?Company;

    public function getCompanyId(): ?int;
}
