<?php

namespace App\Modules\V1\SubBrands\Domain\Models;

use App\Modules\Shared\Support\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[fillable(['name', 'company_id', 'brand_id', 'slug', 'is_active'])]
class SubBrand extends Model implements HasMedia
{
    use BelongsToCompany, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->slug = str($model->name.'-'.$model->company_id)->slug();
        });
    }
}
