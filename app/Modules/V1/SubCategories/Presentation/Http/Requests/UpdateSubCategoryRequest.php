<?php

namespace App\Modules\V1\SubCategories\Presentation\Http\Requests;

use App\Modules\Shared\Support\Rules\ExistsInCurrentCompany;
use App\Modules\Shared\Support\Traits\ValidatesCatalogHierarchy;
use App\Modules\V1\SubCategories\Domain\Models\SubCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubCategoryRequest extends FormRequest
{
    use ValidatesCatalogHierarchy;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'brand_id' => ['nullable', new ExistsInCurrentCompany('brands')],
            'sub_brand_id' => ['nullable', new ExistsInCurrentCompany('sub_brands')],
            'category_id' => ['sometimes', new ExistsInCurrentCompany('categories')],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'sometimes|boolean',
        ];
    }


    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $this->addCatalogHierarchyValidation($validator);
    }


    protected function catalogValidationData(array $data): array
    {
        $subCategory = SubCategory::query()->find($this->route('id'));

        return array_replace([
            'brand_id' => $subCategory?->brand_id,
            'sub_brand_id' => $subCategory?->sub_brand_id,
            'category_id' => $subCategory?->category_id,
        ], $data);
    }
}
