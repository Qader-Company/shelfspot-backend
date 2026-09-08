<?php

namespace App\Modules\V1\Stores\Domain\Models;

use App\Modules\Shared\Support\Traits\BelongsToCompany;
use App\Modules\V1\Tasks\Domain\Models\Task;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['company_id', 'name', 'number', 'latitude', 'longitude', 'address', 'is_active'])]
class Store extends Model
{
    use BelongsToCompany, Filterable, SoftDeletes;

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
