<?php

namespace App\Modules\V1\SubCategories\Presentation\Http\Requests;

use App\Modules\Shared\Support\Rules\ExistsInCurrentCompany;
use App\Modules\Shared\Support\Traits\ValidatesCatalogHierarchy;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubCategoryRequest extends FormRequest
{
    use ValidatesCatalogHierarchy;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'brand_id' => ['nullable', new ExistsInCurrentCompany('brands')],
            'sub_brand_id' => ['nullable', new ExistsInCurrentCompany('sub_brands')],
            'category_id' => ['required', new ExistsInCurrentCompany('categories')],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'required|boolean',
        ];
    }


    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $this->addCatalogHierarchyValidation($validator);
    }
}
