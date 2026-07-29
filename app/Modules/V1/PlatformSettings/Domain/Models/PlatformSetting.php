<?php

namespace App\Modules\V1\PlatformSettings\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        'email',
        'phone',
        'address',
        'description_ar',
        'description_en',
    ];
}
