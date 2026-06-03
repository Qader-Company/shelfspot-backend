<?php

namespace App\Modules\V1\Products\Domain\Models;

use App\Modules\Shared\Support\Traits\BelongsToCompany;
use App\Modules\V1\Brands\Domain\Models\Brand;
use App\Modules\V1\Categories\Domain\Models\Category;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\SubBrands\Domain\Models\SubBrand;
use App\Modules\V1\SubCategories\Domain\Models\SubCategory;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['name', 'company_id', 'brand_id', 'sub_brand_id', 'category_id', 'sub_category_id', 'slug', 'description', 'sku', 'is_active'])]
class Product extends Model implements HasMedia
{
    use BelongsToCompany, InteractsWithMedia, Filterable;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function subBrand(): BelongsTo
    {
        return $this->belongsTo(SubBrand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->slug = str($model->name.'-'.$model->company_id)->slug());
    }
}
