<?php

namespace App\Modules\V1\Stores\Presentation\Http\Requests;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'number' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('stores', 'number')
                    ->where('company_id', $this->companyId())
                    ->ignore($this->route('id')),
            ],
            'latitude' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'address' => ['sometimes', 'required', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function companyId(): ?int
    {
        return app(TenantContextInterface::class)->getCompanyId();
    }
}
