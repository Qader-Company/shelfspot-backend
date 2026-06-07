<?php

namespace App\Modules\V1\Tasks;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    'started_at',
    'completed_at',
    'declined_at',
    'decline_reason',
    'payment_status',
    'charged_at',
])]
class Task extends Model
{
    protected $casts = [
        'date' => 'date',
        'execution_time' => 'datetime:H:i:s',
        'subtotal' => 'decimal:2',
        'total_price' => 'decimal:2',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'declined_at' => 'datetime',
        'charged_at' => 'datetime',
        'status' => TaskStatusEnum::class,
        'payment_status' => TaskPaymentStatusEnum::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

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
}
