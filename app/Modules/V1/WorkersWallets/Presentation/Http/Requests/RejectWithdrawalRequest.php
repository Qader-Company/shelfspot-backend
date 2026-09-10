<?php

namespace App\Modules\V1\WorkersWallets\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
