<?php

namespace App\Modules\Shared\Infrastructure\Tenant;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Companies\Domain\Models\Company;

class TenantContext implements TenantContextInterface
{
    private ?Company $company = null;

    public function setCompany(string $id): bool
    {
        $company = Company::withoutGlobalScopes()->find($id);

        if (is_null($company)) {
            return false;
        }
        $this->company = $company;

        config(['tenant.company_id' => $company->id]);
        return true;

    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function getCompanyId(): ?int
    {
        return $this->company?->id;
    }
}
