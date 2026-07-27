<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayDraftTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // A new date is only needed when the draft's saved execution date is no longer valid.
            'date' => ['sometimes', 'required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:tomorrow'],
        ];
    }
}
