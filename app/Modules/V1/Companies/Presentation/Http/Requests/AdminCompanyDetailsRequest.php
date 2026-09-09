<?php

namespace App\Modules\V1\Companies\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminCompanyDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
        ];
    }
}
