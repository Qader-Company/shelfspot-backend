<?php

namespace App\Modules\V1\Services\Domain\Models;

use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use Astrotomic\Translatable\Translatable;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[fillable('key', 'minimum_price', 'minimum_execution_time', 'is_active')]
class Service extends Model
{
    use Translatable, Filterable;

    public $translatedAttributes = ['description'];
    public $casts = [
        'key' => ServiceTypeEnum::class,
    ];
}
