<?php

namespace App\Modules\V1\Workers\Presentation\Http\Requests;

use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminShowWorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(TaskStatusEnum::class)],
            'not_in_progress' => ['sometimes', 'boolean'],
        ];
    }

    public function taskFilters(): array
    {
        return collect($this->validated())
            ->only(['status', 'not_in_progress'])
            ->all();
    }
}
