<?php

namespace App\Modules\V1\Companies\Domain\Models;

use App\Modules\V1\Brands\Domain\Models\Brand;
use App\Modules\V1\Categories\Domain\Models\Category;
use App\Modules\V1\Companies\Domain\ValueObjects\CompanyIndustryEnum;
use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;
use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\SubBrands\Domain\Models\SubBrand;
use App\Modules\V1\SubCategories\Domain\Models\SubCategory;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[fillable(['name','email','phone','cr_number','industry', 'is_active'])]
class Company extends Model
{
    use Filterable, SoftDeletes;
    protected $casts = [
        'industry' => CompanyIndustryEnum::class,
    ];

    public function users()
    {
        return $this->hasMany(CompanyUser::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function brands()
    {
        return $this->hasMany(Brand::class);
    }

    public function subBrands()
    {
        return $this->hasMany(SubBrand::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class);
    }

}
