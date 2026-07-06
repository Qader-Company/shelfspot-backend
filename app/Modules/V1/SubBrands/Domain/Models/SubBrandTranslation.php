<?php

namespace App\Modules\V1\SubBrands\Domain\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name'])]
class SubBrandTranslation extends Model
{
    public $timestamps = false;
}
