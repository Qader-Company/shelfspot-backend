<?php

namespace App\Modules\V1\WorkersWallets\Domain\Models;

use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WorkerWalletTransactionTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'worker_id',
    'type',
    'amount',
    'balance_after',
    'idempotency_key',
    'reference_type',
    'reference_id',
    'description',
])]
class WorkerWalletTransaction extends Model
{
    protected $casts = [
        'type' => WorkerWalletTransactionTypeEnum::class,
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class)->withTrashed();
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
