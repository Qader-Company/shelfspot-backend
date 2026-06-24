<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRejectTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
