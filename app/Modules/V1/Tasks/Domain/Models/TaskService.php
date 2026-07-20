<?php

namespace App\Modules\V1\Tasks\Domain\Models;

use App\Modules\V1\Services\Domain\Models\Service;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskServiceStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'task_id',
    'service_id',
    'execution_instructions',
    'form_data',
    'unit_price',
    'status',
    'sort_order',
])]
class TaskService extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $casts = [
        'form_data' => 'array',
        'unit_price' => 'decimal:2',
        'status' => TaskServiceStatusEnum::class,
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('planogram_files');
        $this->addMediaCollection('job_order_files');
    }

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

    public function submission(): HasOne
    {
        return $this->hasOne(TaskServiceSubmission::class);
    }
}
