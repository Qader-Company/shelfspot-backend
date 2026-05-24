<?php

namespace App\Modules\Shared\Support\Traits;

trait Filterable
{
    public function acceptedFilters($request, $keys)
    {
        $filter = [];
        foreach ($keys as $key) {
            if($request->filled($key))
                $filter[$key] = $request->query($key);
        }
        return $filter;
    }

}
