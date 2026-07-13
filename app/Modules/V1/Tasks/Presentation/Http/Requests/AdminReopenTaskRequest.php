<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminReopenTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'worker_id' => ['required', 'integer', 'exists:workers,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
