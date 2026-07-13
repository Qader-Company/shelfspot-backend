<?php

namespace App\Modules\V1\Tasks\Domain\Models;

use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentOutcomeEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentTypeEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id',
    'worker_id',
    'assignment_type',
    'assigned_by',
    'assigned_at',
    'unassigned_at',
    'outcome',
    'reason',
])]
class TaskWorkerAssignment extends Model
{
    protected $casts = [
        'assignment_type' => TaskWorkerAssignmentTypeEnum::class,
        'outcome' => TaskWorkerAssignmentOutcomeEnum::class,
        'assigned_at' => 'datetime',
        'unassigned_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
