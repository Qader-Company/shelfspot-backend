<?php

namespace App\Modules\Shared\Support\Traits;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

trait ValidatesTenantOwnership
{
    protected function existsInCurrentCompany(string $table, string $column = 'id'): Exists
    {
        $rule = Rule::exists($table, $column);
        $companyId = app(TenantContextInterface::class)->getCompanyId();

        if ($companyId !== null) {
            $rule->where('company_id', $companyId);
        }

        return $rule;
    }
}
