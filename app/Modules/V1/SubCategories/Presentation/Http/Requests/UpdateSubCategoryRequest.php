<?php

namespace App\Modules\V1\SubCategories\Presentation\Http\Requests;

use App\Modules\Shared\Support\Traits\ValidatesTenantOwnership;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubCategoryRequest extends FormRequest
{
    use ValidatesTenantOwnership;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'brand_id' => ['nullable', $this->existsInCurrentCompany('brands')],
            'sub_brand_id' => ['nullable', $this->existsInCurrentCompany('sub_brands')],
            'category_id' => ['sometimes', $this->existsInCurrentCompany('categories')],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
