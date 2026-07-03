<?php

namespace App\Modules\V1\Categories\Domain\Models;

use App\Modules\Shared\Support\Traits\BelongsToCompany;
use App\Modules\V1\Brands\Domain\Models\Brand;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\SubBrands\Domain\Models\SubBrand;
use App\Modules\V1\SubCategories\Domain\Models\SubCategory;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['name', 'company_id', 'brand_id', 'sub_brand_id', 'is_active'])]
class Category extends Model implements HasMedia
{
    use BelongsToCompany, InteractsWithMedia, Filterable, SoftDeletes;


    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function subBrand(): BelongsTo
    {
        return $this->belongsTo(SubBrand::class);
    }

    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategory::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
