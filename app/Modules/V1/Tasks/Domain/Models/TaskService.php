<?php

namespace App\Modules\V1\Tasks\Domain\Models;

use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'task_id',
    'service_id',
    'execution_instructions',
    'request_details',
    'unit_price',
    'status',
    'sort_order',
])]
class TaskService extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $casts = [
        'request_details' => 'array',
        'unit_price' => 'decimal:2',
        'status' => TaskServiceStatusEnum::class,
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(TaskServiceProduct::class);
    }
}
