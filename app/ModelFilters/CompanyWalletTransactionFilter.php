<?php

namespace App\ModelFilters;

use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use EloquentFilter\ModelFilter;

class CompanyWalletTransactionFilter extends ModelFilter
{
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];

    public function type($value)
    {
        $type = CompanyWalletTransactionTypeEnum::tryFrom($value);
        return $this->when($type, fn($q) => $q->where('type', $type));
    }
}
