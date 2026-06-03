<?php

namespace App\Modules\Shared\Support\Rules;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class ExistsInCurrentCompany implements ValidationRule
{
    public function __construct(
        public string $table,
        public string $column = 'id'
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $companyId = app(TenantContextInterface::class)->getCompanyId();

        $exists = DB::table($this->table)
            ->where($this->column, $value)
            ->when($companyId !== null, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->exists();

        if (! $exists) {
            $fail("The selected {$attribute} is invalid.");
        }
    }
}
