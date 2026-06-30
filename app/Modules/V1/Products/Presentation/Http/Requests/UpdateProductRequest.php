<?php

namespace App\Modules\V1\Products\Presentation\Http\Requests;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\Shared\Support\Rules\ExistsInCurrentCompany;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{

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
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku')
                    ->where('company_id', $this->companyId())
                    ->ignore($this->route('id')),
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $current = $this->currentProduct();
                $brandId = $this->effectiveValue('brand_id', $current);
                $subBrandId = $this->effectiveValue('sub_brand_id', $current);
                $categoryId = $this->effectiveValue('category_id', $current);
                $subCategoryId = $this->effectiveValue('sub_category_id', $current);

                if ($subBrandId && ! $brandId) {
                    $validator->errors()->add('brand_id', 'The brand field is required when sub brand is selected.');
                }

                if ($subCategoryId && ! $categoryId) {
                    $validator->errors()->add('category_id', 'The category field is required when sub category is selected.');
                }

                if ($brandId && $subBrandId && ! $this->subBrandBelongsToBrand((int) $subBrandId, (int) $brandId)) {
                    $validator->errors()->add('sub_brand_id', 'The selected sub brand does not belong to the selected brand.');
                }

                if ($categoryId && $subCategoryId && ! $this->subCategoryBelongsToCategory((int) $subCategoryId, (int) $categoryId)) {
                    $validator->errors()->add('sub_category_id', 'The selected sub category does not belong to the selected category.');
                }
            },
        ];
    }

    private function effectiveValue(string $key, ?object $current): mixed
    {
        return $this->has($key)
            ? $this->input($key)
            : $current?->{$key};
    }

    private function currentProduct(): ?object
    {
        return DB::table('products')
            ->where('id', $this->route('id'))
            ->first(['brand_id', 'sub_brand_id', 'category_id', 'sub_category_id']);
    }

    private function subBrandBelongsToBrand(int $subBrandId, int $brandId): bool
    {
        return DB::table('sub_brands')
            ->where('id', $subBrandId)
            ->where('brand_id', $brandId)
            ->exists();
    }

    private function subCategoryBelongsToCategory(int $subCategoryId, int $categoryId): bool
    {
        return DB::table('sub_categories')
            ->where('id', $subCategoryId)
            ->where('category_id', $categoryId)
            ->exists();
    }

    private function companyId(): ?int
    {
        return app(TenantContextInterface::class)->getCompanyId();
    }
}
