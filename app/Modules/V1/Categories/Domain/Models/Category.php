<?php
namespace App\Modules\V1\Categories\Domain\Models;

use App\Modules\Shared\Support\Traits\BelongsToCompany;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[fillable(['name','company_id','brand_id','sub_brand_id','slug','is_active'])]
class Category extends Model
{
    use BelongsToCompany, Filterable;
    protected static function boot(): void { parent::boot(); static::creating(fn($m)=>$m->slug = str($m->name.'-'.$m->company_id)->slug()); }
}
