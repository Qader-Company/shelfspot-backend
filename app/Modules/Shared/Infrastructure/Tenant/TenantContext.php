<?php

namespace App\Modules\Shared\Infrastructure\Tenant;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Companies\Domain\Models\Company;

class TenantContext implements TenantContextInterface
{
    private ?string $slug = null;
    private ?Company $company = null;

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): bool
    {
        $company = Company::withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();

        if (is_null($company)) {
            return false;
        }

        $this->setCompany($company);

        return true;
    }

    public function setCompany(Company $company): void
    {
        $this->company = $company;
        $this->slug = $company->slug;

        config(['tenant.slug' => $this->slug]);
        config(['tenant.company_id' => $company->id]);
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
