<?php

namespace App\Modules\Shared\Support\Traits;

use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

trait ValidatesCatalogHierarchy
{
    protected function addCatalogHierarchyValidation(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $this->catalogValidationData($validator->getData());

            $brandId = $this->catalogValue($data, 'brand_id');
            $subBrandId = $this->catalogValue($data, 'sub_brand_id');
            $categoryId = $this->catalogValue($data, 'category_id');
            $subCategoryId = $this->catalogValue($data, 'sub_category_id');

            if ($subBrandId !== null && $brandId !== null && ! $this->catalogExists('sub_brands', $subBrandId, ['brand_id' => $brandId])) {
                $validator->errors()->add('sub_brand_id', __('validation.exists', ['attribute' => 'sub brand']));
            }

            if ($categoryId !== null && ! $this->catalogExists('categories', $categoryId, [
                'brand_id' => $brandId,
                'sub_brand_id' => $subBrandId,
            ])) {
                $validator->errors()->add('category_id', __('validation.exists', ['attribute' => 'category']));
            }

            if ($subCategoryId !== null && ! $this->catalogExists('sub_categories', $subCategoryId, [
                'brand_id' => $brandId,
                'sub_brand_id' => $subBrandId,
                'category_id' => $categoryId,
            ])) {
                $validator->errors()->add('sub_category_id', __('validation.exists', ['attribute' => 'sub category']));
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function catalogValidationData(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    protected function catalogExists(string $table, mixed $id, array $conditions = []): bool
    {
        $companyId = app(TenantContextInterface::class)->getCompanyId();

        return DB::table($table)
            ->where('id', $id)
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->when($conditions, function ($query) use ($conditions) {
                foreach ($conditions as $column => $value) {
                    if ($value !== null) {
                        $query->where($column, $value);
                    }
                }
            })
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function catalogValue(array $data, string $key): mixed
    {
        return array_key_exists($key, $data) && filled($data[$key]) ? $data[$key] : null;
    }
}
