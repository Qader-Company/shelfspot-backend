<?php

namespace App\Modules\V1\CompaniesWallets\Presentation\Http\Requests;

use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyWalletIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(CompanyWalletTransactionTypeEnum::class)],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function filters(): array
    {
        return collect($this->validated())
            ->only(['type', 'date_from', 'date_to'])
            ->all();
    }
}
