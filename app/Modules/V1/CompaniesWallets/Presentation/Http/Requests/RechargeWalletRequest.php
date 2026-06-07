<?php

namespace App\Modules\V1\CompaniesWallets\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RechargeWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1',
            'description' => 'sometimes|nullable|string|max:1000',
        ];
    }
}
