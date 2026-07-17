<?php

namespace App\Modules\V1\Services\Domain\Models;

use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use Astrotomic\Translatable\Translatable;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable('key', 'price', 'is_active')]
class Service extends Model
{
    use Filterable, Translatable;

    public $translatedAttributes = ['description'];

    public $casts = [
        'key' => ServiceTypeEnum::class,
    ];
}
