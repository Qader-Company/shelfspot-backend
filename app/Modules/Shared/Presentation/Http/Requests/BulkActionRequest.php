<?php

namespace App\Modules\Shared\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function ids(): array
    {
        return $this->validated('ids');
    }
}
