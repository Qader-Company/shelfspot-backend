<?php

namespace App\Modules\Shared\Domain\Contracts;

use App\Modules\V1\Companies\Domain\Models\Company;

interface TenantContextInterface
{
    public function getSlug(): ?string;

    public function setSlug(string $slug): bool;

    public function setCompany(Company $company): void;

    public function getCompany(): ?Company;

    public function getCompanyId(): ?int;
}
