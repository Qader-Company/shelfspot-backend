<?php

namespace App\Modules\V1\Categories\Domain\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name'])]
class CategoryTranslation extends Model
{
    public $timestamps = false;
}
