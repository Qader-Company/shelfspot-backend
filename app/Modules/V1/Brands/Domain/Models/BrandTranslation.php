<?php

namespace App\Modules\V1\Brands\Domain\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name'])]
class BrandTranslation extends Model
{
    public $timestamps = false;
}
