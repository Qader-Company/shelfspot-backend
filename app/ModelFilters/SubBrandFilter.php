<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class SubBrandFilter extends ModelFilter
{
    /**
     * Related Models that have ModelFilters as well as the method on the ModelFilter
     * As [relationMethod => [input_key1, input_key2]].
     *
     * @var array
     */
    public $relations = [];

    public function name($name)
    {
        return $this->whereTranslationLike('name', "%{$name}%");
    }

    public function active($isActive)
    {
        return $this->where('is_active', $isActive);
    }

    public function brandId($brandId)
    {
        return $this->where('brand_id', $brandId);
    }
}
