<?php

namespace App\Modules\V1\Brands\Domain\Models;

use App\Modules\Shared\Support\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[fillable(['name', 'company_id', 'slug', 'is_active'])]
class Brand extends Model implements HasMedia
{
    use BelongsToCompany, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile();
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->slug = str($model->name.'-'.$model->company_id)->slug();
        });
    }

}
