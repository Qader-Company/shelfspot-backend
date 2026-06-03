<?php

namespace App\Modules\V1\Categories\Presentation\Http\Requests;

use App\Modules\Shared\Support\Traits\ValidatesTenantOwnership;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    use ValidatesTenantOwnership;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'brand_id' => ['nullable', $this->existsInCurrentCompany('brands')],
            'sub_brand_id' => ['nullable', $this->existsInCurrentCompany('sub_brands')],
            'is_active' => 'sometimes|boolean',
        ];
    }
}
