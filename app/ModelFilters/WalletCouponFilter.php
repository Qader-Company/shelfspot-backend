<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class WalletCouponFilter extends ModelFilter
{
    public $relations = [];

    public function active($active)
    {
        return $this->where('is_active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
    }

    public function search($value)
    {
        return $this->where(function ($query) use ($value) {
            $query->where('code', 'like', "%$value%")
                ->orWhere('notes', 'like', "%$value%");
        });
    }
}
