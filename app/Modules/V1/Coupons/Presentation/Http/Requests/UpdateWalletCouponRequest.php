<?php

namespace App\Modules\V1\Coupons\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWalletCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon') ?? $this->route('id');

        return [
            'code' => ['sometimes', 'string', 'max:100', Rule::unique('wallet_coupons', 'code')->ignore($couponId)],
            'amount' => ['sometimes', 'numeric', 'min:1'],
            'max_redemptions' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'assigned_company_id' => ['sometimes', 'nullable', 'exists:companies,id'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
