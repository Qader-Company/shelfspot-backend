<?php

namespace App\Modules\V1\Brands\Domain\Models;

use App\Modules\Shared\Support\Traits\BelongsToCompany;
use App\Modules\V1\Categories\Domain\Models\Category;
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
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['name', 'company_id', 'slug', 'is_active'])]
class Brand extends Model implements HasMedia
{
    use BelongsToCompany, InteractsWithMedia, Filterable, SoftDeletes;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile();
    }

    public function subBrands(): HasMany
    {
        return $this->hasMany(SubBrand::class);
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

    public static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->slug = str($model->name.'-'.$model->company_id.'-'.Str::random(6))->slug();
        });
    }



}
