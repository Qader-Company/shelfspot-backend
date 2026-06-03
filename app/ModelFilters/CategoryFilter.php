<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class CategoryFilter extends ModelFilter
{
    public function name($name)
    {
        return $this->where('name', 'like', "%$name%");
    }

    public function active($isActive)
    {
        return $this->where('is_active', $isActive);
    }

    public function brandId($brandId)
    {
        return $this->where('brand_id', $brandId);
    }

    public function subBrandId($subBrandId)
    {
        return $this->where('sub_brand_id', $subBrandId);
    }
}
