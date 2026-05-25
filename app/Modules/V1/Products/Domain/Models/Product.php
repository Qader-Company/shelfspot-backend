<?php

namespace App\Modules\V1\Products\Domain\Models;

use App\Modules\Shared\Support\Traits\BelongsToCompany;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[fillable(['name', 'company_id', 'brand_id', 'sub_brand_id', 'category_id', 'sub_category_id', 'slug', 'description', 'sku', 'is_active'])]
class Product extends Model implements HasMedia
{
    use BelongsToCompany, InteractsWithMedia, Filterable;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($m) => $m->slug = str($m->name.'-'.$m->company_id)->slug());
    }
}
