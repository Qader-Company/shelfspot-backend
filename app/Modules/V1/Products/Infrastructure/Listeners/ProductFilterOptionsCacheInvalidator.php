<?php

namespace App\Modules\V1\Products\Infrastructure\Listeners;

use App\Modules\V1\Brands\Domain\Models\Brand;
use App\Modules\V1\Categories\Domain\Models\Category;
use App\Modules\V1\Products\Application\Caching\ProductFilterOptionsCache;
use App\Modules\V1\SubBrands\Domain\Models\SubBrand;
use App\Modules\V1\SubCategories\Domain\Models\SubCategory;
use Illuminate\Database\Eloquent\Model;

final readonly class ProductFilterOptionsCacheInvalidator
{
    public function __construct(private ProductFilterOptionsCache $cache) {}

    public function register(): void
    {
        foreach ([Brand::class, SubBrand::class, Category::class, SubCategory::class] as $model) {
            $model::saved(fn (Model $model) => $this->invalidate($model));
            $model::deleted(fn (Model $model) => $this->invalidate($model));
            $model::restored(fn (Model $model) => $this->invalidate($model));
        }
    }

    private function invalidate(Model $model): void
    {
        $companyIds = array_values(array_filter([
            $model->getAttribute('company_id'),
            $model->getOriginal('company_id'),
        ]));

        $this->cache->incrementVersionAfterCommit(...$companyIds);
    }
}
