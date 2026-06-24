<?php

namespace App\Modules\Shared\Support\Traits;

use Illuminate\Http\Request;

trait Filterable
{
    protected function acceptedFilters(Request $request, array $filters): array
    {
        return collect($request->only($filters))
            ->filter(fn ($value) => !is_null($value) && $value !== '')
            ->toArray();
    }

}
