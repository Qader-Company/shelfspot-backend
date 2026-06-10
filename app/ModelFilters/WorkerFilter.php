<?php

namespace App\ModelFilters;

use EloquentFilter\ModelFilter;
use Illuminate\Database\Eloquent\Builder;

class WorkerFilter extends ModelFilter
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
        return $this->where(function (Builder $query) use ($search) {
                $query->where('phone', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function (Builder $query) use ($search) {
                        $query->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
    }

}
