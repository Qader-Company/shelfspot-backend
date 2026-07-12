<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyAcceptTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feedback' => ['nullable', 'array:platform_comment,worker_comment,overall_comment'],
            'feedback.platform_comment' => ['nullable', 'string', 'max:2000'],
            'feedback.worker_comment' => ['nullable', 'string', 'max:2000'],
            'feedback.overall_comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
