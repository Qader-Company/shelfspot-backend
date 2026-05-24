<?php
namespace App\Modules\V1\SubCategories\Domain\Models;

use App\Modules\Shared\Support\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[fillable(['name','company_id','brand_id','sub_brand_id','category_id','slug','is_active'])]
class SubCategory extends Model implements HasMedia
{
    use BelongsToCompany, InteractsWithMedia;
    public function registerMediaCollections(): void { $this->addMediaCollection('image')->singleFile(); }
    protected static function boot(): void { parent::boot(); static::creating(fn($m)=>$m->slug = str($m->name.'-'.$m->company_id)->slug()); }
}
