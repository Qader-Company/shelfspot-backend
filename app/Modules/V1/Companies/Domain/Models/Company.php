<?php

namespace App\Modules\V1\Companies\Domain\Models;

use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
#[fillable(['name','slug','email','phone','cr_number','industry', 'is_active'])]
class Company extends Model
{
    protected $casts = [
        'industry' => CompanyIndustryEnum::class,
    ];

    public function users()
    {
        return $this->hasMany(CompanyUser::class);
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->slug = str($model->name.'-'.$model->industry->name.'-'.$model->cr_number)->slug();
        });
    }
}
