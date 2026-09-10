<?php

namespace App\Modules\V1\WorkersWallets\Domain\Models;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WithdrawalMethodEnum;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WithdrawalStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'worker_id',
    'amount',
    'method',
    'status',
    'payout_details',
    'processed_by',
    'processed_at',
    'paid_at',
    'rejection_reason',
])]
class WithdrawalRequest extends Model
{
    protected $casts = [
        'amount' => 'decimal:2',
        'method' => WithdrawalMethodEnum::class,
        'status' => WithdrawalStatusEnum::class,
        'payout_details' => 'array',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class)->withTrashed();
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
