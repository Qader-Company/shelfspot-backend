<?php

namespace App\Modules\V1\CompanyAdmins\Domain\Models;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[fillable(['company_id','user_id','is_owner', 'is_active'])]
class CompanyUser extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
