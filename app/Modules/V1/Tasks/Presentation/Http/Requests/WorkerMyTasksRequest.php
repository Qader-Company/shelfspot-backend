<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Requests;

use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkerMyTasksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(TaskStatusEnum::class)],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            // Query-string booleans arrive as strings, so accept both API-friendly
            // true/false and the conventional 1/0 values.
            'reassigned_to_me' => ['sometimes', 'in:true,false,1,0'],
        ];
    }

    public function filters(): array
    {
        $filters = collect($this->validated())
            ->only(['status', 'date_from', 'date_to'])
            ->all();

        if ($this->boolean('reassigned_to_me')) {
            $filters['reassigned_to_me'] = true;
        }

        return $filters;
    }
}
