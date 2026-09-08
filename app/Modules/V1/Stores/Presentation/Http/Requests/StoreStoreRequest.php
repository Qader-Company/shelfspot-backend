<?php

namespace App\Modules\V1\Stores\Presentation\Http\Requests;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('stores', 'number')->where('company_id', $this->companyId()),
            ],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['required', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function companyId(): ?int
    {
        return app(TenantContextInterface::class)->getCompanyId();
    }
}
