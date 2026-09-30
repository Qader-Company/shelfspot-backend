<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminReopenTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'worker_id' => [
                'required',
                'integer',
                Rule::exists('workers', 'id')->where(
                    fn (Builder $query) => $query
                        ->whereNull('deleted_at')
                        ->where('is_active', true)
                ),
            ],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
