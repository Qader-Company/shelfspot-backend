<?php

namespace App\Modules\V1\Products\Presentation\Http\Requests;

use App\Modules\Shared\Support\Rules\ExistsInCurrentCompany;
use App\Modules\Shared\Support\Traits\ValidatesCatalogHierarchy;
use App\Modules\V1\Products\Domain\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    use ValidatesCatalogHierarchy;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'brand_id' => ['nullable', new ExistsInCurrentCompany('brands')],
            'sub_brand_id' => ['nullable', new ExistsInCurrentCompany('sub_brands')],
            'category_id' => ['nullable', new ExistsInCurrentCompany('categories')],
            'sub_category_id' => ['nullable', new ExistsInCurrentCompany('sub_categories')],
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:255',
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
        $product = Product::query()->find($this->route('id'));

        return array_replace([
            'brand_id' => $product?->brand_id,
            'sub_brand_id' => $product?->sub_brand_id,
            'category_id' => $product?->category_id,
            'sub_category_id' => $product?->sub_category_id,
        ], $data);
    }
}
