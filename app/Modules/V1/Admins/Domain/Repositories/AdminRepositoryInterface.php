<?php

namespace App\Modules\V1\Admins\Domain\Repositories;

use App\Modules\V1\Admins\Domain\Models\ShelfSpotAdmin;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface AdminRepositoryInterface
{
    public function admins(array $filters = []): Collection;

    public function profileFor(User $user): ShelfSpotAdmin;

    public function createProfile(User $user, bool $isActive = true): ShelfSpotAdmin;

    public function updateProfile(ShelfSpotAdmin $admin, array $attributes): ShelfSpotAdmin;

    public function deleteProfile(ShelfSpotAdmin $admin): void;
}
