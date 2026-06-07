<?php

namespace App\Modules\V1\Coupons\Domain\Models;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletCouponRedemption extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'redeemed_at' => 'datetime',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(WalletCoupon::class, 'wallet_coupon_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }
}
