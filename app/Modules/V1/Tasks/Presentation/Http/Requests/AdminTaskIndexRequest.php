<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Requests;

use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminTaskIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['sometimes', 'integer', 'exists:companies,id'],
            'status' => ['sometimes', Rule::enum(TaskStatusEnum::class)],
            'payment_status' => ['sometimes', Rule::enum(TaskPaymentStatusEnum::class)],
            'assigned_worker_id' => ['sometimes', 'integer', 'exists:workers,id'],
            'created_by' => ['sometimes', 'integer', 'exists:users,id'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function filters(): array
    {
        return collect($this->validated())
            ->only([
                'company_id',
                'status',
                'payment_status',
                'assigned_worker_id',
                'created_by',
                'date_from',
                'date_to',
            ])
            ->all();
    }
}
