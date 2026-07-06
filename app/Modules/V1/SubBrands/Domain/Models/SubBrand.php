<?php

namespace App\Modules\V1\SubBrands\Domain\Models;

use App\Modules\Shared\Support\Traits\BelongsToCompany;
use App\Modules\Shared\Support\Traits\DeletesMediaOnForceDelete;
use App\Modules\V1\Brands\Domain\Models\Brand;
use App\Modules\V1\Categories\Domain\Models\Category;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\SubCategories\Domain\Models\SubCategory;
use Astrotomic\Translatable\Translatable;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['company_id', 'brand_id', 'is_active'])]
class SubBrand extends Model implements HasMedia
{
    use Translatable, BelongsToCompany, InteractsWithMedia, DeletesMediaOnForceDelete, Filterable, SoftDeletes;

    public $translatedAttributes = ['name'];


    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
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
