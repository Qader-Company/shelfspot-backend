<?php

namespace App\Modules\V1\Workers\Domain\Models;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Users\Domain\Models\User;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'phone',
    'is_active',
    'wallet_balance',
    'last_latitude',
    'last_longitude',
    'location_updated_at',
])]
class Worker extends Model
{
    use Filterable;
    protected $casts = [
        'is_active' => 'boolean',
        'wallet_balance' => 'decimal:2',
        'last_latitude' => 'decimal:7',
        'last_longitude' => 'decimal:7',
        'location_updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_worker_id');
    }
}
