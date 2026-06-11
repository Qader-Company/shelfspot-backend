<?php

namespace App\Modules\V1\Tasks\Domain\Models;

use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'task_service_id',
    'worker_id',
    'form_data',
    'status',
    'completed_at',
])]
class TaskServiceSubmission extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $casts = [
        'form_data' => 'array',
        'completed_at' => 'datetime',
    ];

    public function taskService(): BelongsTo
    {
        return $this->belongsTo(TaskService::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
