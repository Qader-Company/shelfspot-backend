<?php

namespace App\Modules\V1\Services\Domain\Models;

use App\Modules\V1\Services\Domain\ValueObjects\ServiceTypeEnum;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[fillable('description')]
class ServiceTranslation extends Model
{
    public $timestamps = false;
}
