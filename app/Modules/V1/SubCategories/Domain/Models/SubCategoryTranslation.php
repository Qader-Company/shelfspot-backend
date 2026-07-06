<?php

namespace App\Modules\V1\SubCategories\Domain\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name'])]
class SubCategoryTranslation extends Model
{
    public $timestamps = false;
}
