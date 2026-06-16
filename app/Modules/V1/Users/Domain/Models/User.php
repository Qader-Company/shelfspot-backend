<?php

namespace App\Modules\V1\Users\Domain\Models;

use App\Modules\V1\Admins\Domain\Models\ShelfSpotAdmin;
use App\Modules\V1\Companies\Domain\Models\CompanyUser;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'type', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'type' => PortalTypeEnum::class,
        ];
    }

    public function companyUser()
    {
        return $this->hasOne(CompanyUser::class);
    }

    public function admin()
    {
        return $this->hasOne(ShelfSpotAdmin::class);
    }

    public function worker()
    {
        return $this->hasOne(Worker::class);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
