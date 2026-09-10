<?php

namespace App\Modules\V1\WorkersWallets\Presentation\Http\Requests;

use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WithdrawalMethodEnum;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WithdrawalStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminWithdrawalIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'worker_id' => ['sometimes', 'integer', 'exists:workers,id'],
            'status' => ['sometimes', Rule::enum(WithdrawalStatusEnum::class)],
            'method' => ['sometimes', Rule::enum(WithdrawalMethodEnum::class)],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function filters(): array
    {
        return $this->safe()->only([
            'search',
            'worker_id',
            'status',
            'method',
            'date_from',
            'date_to',
        ]);
    }
}
