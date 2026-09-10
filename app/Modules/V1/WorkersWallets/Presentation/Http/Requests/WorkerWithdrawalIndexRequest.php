<?php

namespace App\Modules\V1\WorkersWallets\Presentation\Http\Requests;

use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WithdrawalStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkerWithdrawalIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(WithdrawalStatusEnum::class)],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function filters(): array
    {
        return $this->safe()->only(['status', 'date_from', 'date_to']);
    }
}
