<?php

namespace App\Modules\V1\Products\Domain\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description'])]
class ProductTranslation extends Model
{
    public $timestamps = false;
}
