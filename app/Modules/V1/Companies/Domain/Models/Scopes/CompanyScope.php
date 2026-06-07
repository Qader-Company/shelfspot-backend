<?php

namespace App\Modules\V1\Companies\Domain\Models\Scopes;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $id = app(TenantContextInterface::class)->getCompanyId();

        if ($id !== null) {
            $builder->where('company_id', $id);
        }
    }
}
