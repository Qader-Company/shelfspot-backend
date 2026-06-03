<?php

namespace App\Modules\V1\Categories\Presentation\Http\Requests;

use App\Modules\Shared\Support\Rules\ExistsInCurrentCompany;
use App\Modules\Shared\Support\Traits\ValidatesCatalogHierarchy;
use App\Modules\V1\Categories\Domain\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    use ValidatesCatalogHierarchy;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'brand_id' => ['nullable', new ExistsInCurrentCompany('brands')],
            'sub_brand_id' => ['nullable', new ExistsInCurrentCompany('sub_brands')],
            'is_active' => 'sometimes|boolean',
        ];
    }


    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $this->addCatalogHierarchyValidation($validator);
    }


    protected function catalogValidationData(array $data): array
    {
        $category = Category::query()->find($this->route('id'));

        return array_replace([
            'brand_id' => $category?->brand_id,
            'sub_brand_id' => $category?->sub_brand_id,
        ], $data);
    }
}
