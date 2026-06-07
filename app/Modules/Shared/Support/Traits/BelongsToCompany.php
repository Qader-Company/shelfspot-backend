<?php

namespace App\Modules\Shared\Support\Traits;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope());

        static::creating(function ($model) {
            if (! empty($model->company_id)) {
                return;
            }

            $companyId = app(TenantContextInterface::class)->getCompanyId();

            if ($companyId !== null) {
                $model->company_id = $companyId;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getCompanyId(): int
    {
        return $this->company_id;
    }

}
