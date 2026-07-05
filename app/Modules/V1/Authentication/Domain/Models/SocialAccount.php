<?php

namespace App\Modules\V1\Authentication\Domain\Models;

use App\Modules\V1\Authentication\Domain\ValueObjects\SocialProviderEnum;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'provider', 'provider_user_id', 'email', 'name', 'avatar'])]
class SocialAccount extends Model
{
    protected function casts(): array
    {
        return [
            'provider' => SocialProviderEnum::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
