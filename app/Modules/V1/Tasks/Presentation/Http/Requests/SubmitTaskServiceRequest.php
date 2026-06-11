<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitTaskServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'form_data' => ['nullable', 'array'],
            'submission_files' => ['nullable', 'array'],
            'submission_files.*' => ['nullable', 'array'],
            'submission_files.*.*' => ['file', 'max:10240'],
        ];
    }

    public function formData(): array
    {
        return $this->validated('form_data') ?? [];
    }

    public function submissionFiles(): array
    {
        return $this->allFiles()['submission_files'] ?? [];
    }
}
