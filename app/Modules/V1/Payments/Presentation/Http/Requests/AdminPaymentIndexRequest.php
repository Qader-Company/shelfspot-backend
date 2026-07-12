<?php

namespace App\Modules\V1\Payments\Presentation\Http\Requests;

use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminPaymentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'company_id' => ['sometimes', 'integer', 'exists:companies,id'],
            'status' => ['sometimes', 'string', Rule::in([...TaskPaymentStatusEnum::values(), 'completed'])],
            'payment_status' => ['sometimes', Rule::enum(TaskPaymentStatusEnum::class)],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function filters(): array
    {
        return collect($this->validated())
            ->only([
                'search',
                'company_id',
                'status',
                'payment_status',
                'date_from',
                'date_to',
            ])
            ->all();
    }

    public function paymentStatus(): ?TaskPaymentStatusEnum
    {
        $status = $this->validated('payment_status') ?? $this->validated('status');

        if ($status === 'completed') {
            return TaskPaymentStatusEnum::CHARGED;
        }

        return is_string($status) ? TaskPaymentStatusEnum::tryFrom($status) : null;
    }
}
