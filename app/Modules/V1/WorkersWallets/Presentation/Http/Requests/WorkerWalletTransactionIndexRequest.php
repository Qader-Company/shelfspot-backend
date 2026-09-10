<?php

namespace App\Modules\V1\WorkersWallets\Presentation\Http\Requests;

use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WorkerWalletTransactionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkerWalletTransactionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(WorkerWalletTransactionTypeEnum::class)],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function filters(): array
    {
        return $this->safe()->only(['type', 'date_from', 'date_to']);
    }
}
