<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class ProductFilter extends ModelFilter
{
    public function search($value)
    {
        return $this->where(function ($query) use ($value) {
            $query->where('name', 'like', "%$value%")
                ->orWhere('sku', 'like', "%$value%")
                ->orWhere('barcode', 'like', "%$value%");
        });
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

    public function categoryId($categoryId)
    {
        return $this->where('category_id', $categoryId);
    }

    public function subCategoryId($subCategoryId)
    {
        return $this->where('sub_category_id', $subCategoryId);
    }
}
