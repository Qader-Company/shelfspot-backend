<?php

namespace App\Modules\V1\Products\Application\Services;

use App\Modules\V1\Brands\Domain\Models\Brand;
use App\Modules\V1\Categories\Domain\Models\Category;
use App\Modules\V1\Products\Application\Caching\ProductFilterOptionsCache;
use App\Modules\V1\SubBrands\Domain\Models\SubBrand;
use App\Modules\V1\SubCategories\Domain\Models\SubCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductFilterOptionsService
{
    public function __construct(private readonly ProductFilterOptionsCache $cache) {}

    public function resolve(array $filters): array
    {
        return $this->cache->remember($filters, fn (): array => $this->buildResponse($filters));
    }

    private function buildResponse(array $filters): array
    {
        $brandId = (int) $filters['brand_id'];
        $subBrandId = $filters['sub_brand_id'] ?? null;
        $categoryId = $filters['category_id'] ?? null;

        $brand = Brand::query()->with('translations')->whereKey($brandId)->first();
        if (! $brand) {
            return $this->emptyResponse($filters);
        }

        $subBrandQuery = SubBrand::query()
            ->where('brand_id', $brandId)
            ->where('is_active', true);

        if ($subBrandId) {
            $subBrandQuery->whereKey($subBrandId);
        }

        $subBrandOptions = $this->translatedOptions($subBrandQuery);

        $categoryQuery = Category::query()
            ->where('brand_id', $brandId)
            ->where('is_active', true);

        if ($subBrandId) {
            $categoryQuery->where('sub_brand_id', $subBrandId);
        }

        $categoryOptions = $this->translatedOptions($categoryQuery);

        $subCategoryQuery = SubCategory::query()
            ->where('brand_id', $brandId)
            ->where('is_active', true);

        if ($subBrandId) {
            $subCategoryQuery->where('sub_brand_id', $subBrandId);
        }

        if ($categoryId) {
            $subCategoryQuery->where('category_id', $categoryId);
        }

        $subCategoryOptions = $this->translatedOptions($subCategoryQuery);

        return [
            'data' => [
                'brands' => [$this->toOption($brand)],
                'sub_brands' => $subBrandOptions->map(fn (SubBrand $subBrand) => $this->toOption($subBrand))->values(),
                'categories' => $categoryOptions->map(fn (Category $category) => $this->toOption($category))->values(),
                'sub_categories' => $subCategoryOptions->map(fn (SubCategory $subCategory) => $this->toOption($subCategory))->values(),
            ],
            'meta' => [
                'applied_filters' => [
                    'brand_id' => $brandId,
                    'sub_brand_id' => $subBrandId,
                    'category_id' => $categoryId,
                    'sub_category_id' => $filters['sub_category_id'] ?? null,
                ],
            ],
        ];
    }

    private function toOption(object $model): array
    {
        return [
            'id' => $model->id,
            'label' => $model->name,
        ];
    }

    private function translatedOptions(Builder $query): Collection
    {
        return $query
            ->with('translations')
            ->get(['id'])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function emptyResponse(array $filters): array
    {
        return [
            'data' => [
                'brands' => [],
                'sub_brands' => [],
                'categories' => [],
                'sub_categories' => [],
            ],
            'meta' => [
                'applied_filters' => $filters,
            ],
        ];
    }
}
