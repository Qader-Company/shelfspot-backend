<?php

namespace App\Modules\V1\Coupons\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWalletCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', Rule::unique('wallet_coupons', 'code')],
            'amount' => ['required', 'numeric', 'min:1'],
            'max_redemptions' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:today'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
