<?php

namespace App\Modules\V1\Coupons\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletCouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'amount' => $this->amount,
            'max_redemptions' => $this->max_redemptions,
            'redemptions_count' => $this->redemptions_count,
            'expires_at' => $this->expires_at?->toISOString(),
            'is_active' => (bool) $this->is_active,
            'is_expired' => $this->isExpired(),
            'has_remaining_redemptions' => $this->hasRemainingRedemptions(),
            'assigned_company' => $this->whenLoaded('assignedCompany', fn () => $this->assignedCompany ? [
                'id' => $this->assignedCompany->id,
                'name' => $this->assignedCompany->name,
                'slug' => $this->assignedCompany->slug,
            ] : null),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ] : null),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
