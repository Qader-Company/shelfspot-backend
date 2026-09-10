<?php

namespace App\Modules\V1\WorkersWallets\Presentation\Http\Requests;

use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WithdrawalMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWithdrawalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('iban')) {
            $this->merge([
                'iban' => strtoupper(str_replace(' ', '', (string) $this->input('iban'))),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'method' => ['required', Rule::enum(WithdrawalMethodEnum::class)],
            'iban' => [
                'nullable',
                'required_if:method,'.WithdrawalMethodEnum::BANK_ACCOUNT->value,
                'prohibited_unless:method,'.WithdrawalMethodEnum::BANK_ACCOUNT->value,
                'string',
                'regex:/^[A-Z0-9]{15,34}$/',
            ],
            'wallet_number' => [
                'nullable',
                'required_if:method,'.WithdrawalMethodEnum::WALLET->value,
                'prohibited_unless:method,'.WithdrawalMethodEnum::WALLET->value,
                'string',
                'max:30',
            ],
            'wallet_provider' => [
                'nullable',
                'prohibited_unless:method,'.WithdrawalMethodEnum::WALLET->value,
                'string',
                'max:100',
            ],
        ];
    }
}
