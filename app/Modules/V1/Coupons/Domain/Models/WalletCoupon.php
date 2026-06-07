<?php

namespace App\Modules\V1\Coupons\Domain\Models;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Users\Domain\Models\User;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalletCoupon extends Model
{
    use Filterable;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'max_redemptions' => 'integer',
        'redemptions_count' => 'integer',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function assignedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'assigned_company_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(WalletCouponRedemption::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasRemainingRedemptions(): bool
    {
        return $this->max_redemptions === null || $this->redemptions_count < $this->max_redemptions;
    }

    public function isAssignedToCompany(int $companyId): bool
    {
        return $this->assigned_company_id === null || (int) $this->assigned_company_id === $companyId;
    }

    public function isRedeemableForCompany(int $companyId): bool
    {
        return $this->is_active
            && ! $this->isExpired()
            && $this->hasRemainingRedemptions()
            && $this->isAssignedToCompany($companyId);
    }
}
