<?php

namespace App\ModelFilters;

use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use EloquentFilter\ModelFilter;

class CompanyFilter extends ModelFilter
{
    /**
     * Related Models that have ModelFilters as well as the method on the ModelFilter
     * As [relationMethod => [input_key1, input_key2]].
     *
     * @var array
     */
    public $relations = [];

    public function active($active)
    {
        return $this->where('is_active', $active);
    }

    public function search($search)
    {
        return $this->where(function ($query) use ($search) {
            $query->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
                ->orWhere('phone', 'like', "%$search%");
        });
    }

    public function industry($industry)
    {
        $industry = CompanyIndustryEnum::tryFrom($industry);

        return $this->where('industry', $industry);
    }
}
