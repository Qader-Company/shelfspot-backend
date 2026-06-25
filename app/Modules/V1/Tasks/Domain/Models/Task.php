<?php

namespace App\Modules\V1\Tasks\Domain\Models;

use App\Modules\Shared\Support\Traits\BelongsToCompany;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workers\Domain\Models\Worker;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'date',
    'execution_time',
    'estimated_duration_minutes',
    'longitude',
    'latitude',
    'location_name',
    'address',
    'subtotal',
    'total_price',
    'rescheduled_from_task_id',
    'notes',
    'status',
    'created_by',
    'assigned_worker_id',
    'expires_at',
    'accepted_at',
    'start_deadline_at',
    'start_deadline_extension_minutes',
    'start_deadline_extended_at',
    'started_at',
    'completed_at',
    'rejected_at',
    'rejection_reason',
    'company_accepted_at',
    'auto_accept_at',
    'auto_accepted_at',
    'reopened_at',
    'reopen_reason',
//    'declined_at',
//    'decline_reason',
    'worker_cancelled_at',
    'worker_cancel_reason',
    'company_deleted_at',
    'company_purged_at',
    'payment_status',
    'charged_at',
])]
class Task extends Model
{
    use Filterable, BelongsToCompany;

    protected $casts = [
        'date' => 'date',
        'execution_time' => 'datetime:H:i:s',
        'subtotal' => 'decimal:2',
        'total_price' => 'decimal:2',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'start_deadline_at' => 'datetime',
        'start_deadline_extended_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'company_accepted_at' => 'datetime',
        'auto_accept_at' => 'datetime',
        'auto_accepted_at' => 'datetime',
        'reopened_at' => 'datetime',
//        'declined_at' => 'datetime',
        'worker_cancelled_at' => 'datetime',
        'company_deleted_at' => 'datetime',
        'company_purged_at' => 'datetime',
        'charged_at' => 'datetime',
        'status' => TaskStatusEnum::class,
        'payment_status' => TaskPaymentStatusEnum::class,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedWorker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'assigned_worker_id');
    }

    public function rescheduledFromTask(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rescheduled_from_task_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(TaskService::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(TaskStatusHistory::class);
    }

    public function reviewMessages(): HasMany
    {
        return $this->hasMany(TaskReviewMessage::class);
    }
}
