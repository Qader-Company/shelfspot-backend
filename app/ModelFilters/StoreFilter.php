<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;

class StoreFilter extends ModelFilter
{
    public function search(mixed $value)
    {
        return $this->where(function ($query) use ($value) {
            $query->where('name', 'like', "%{$value}%")
                ->orWhere('number', 'like', "%{$value}%")
                ->orWhere('address', 'like', "%{$value}%");
        });
    }

    public function active(mixed $isActive)
    {
        return $this->where('is_active', $isActive);
    }
}
