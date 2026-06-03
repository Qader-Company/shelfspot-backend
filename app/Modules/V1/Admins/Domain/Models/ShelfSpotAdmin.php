<?php

namespace App\Modules\V1\Admins\Domain\Models;

use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[fillable('user_id', 'is_active')]
class ShelfSpotAdmin extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
