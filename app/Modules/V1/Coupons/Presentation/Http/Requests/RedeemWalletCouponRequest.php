<?php

namespace App\Modules\V1\Coupons\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedeemWalletCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100'],
        ];
    }
}
