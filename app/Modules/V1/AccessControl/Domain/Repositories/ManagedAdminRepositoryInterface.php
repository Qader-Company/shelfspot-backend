<?php

namespace App\Modules\V1\AccessControl\Domain\Repositories;

use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ManagedAdminRepositoryInterface
{
    public function shelfSpotAdmins(array $filters = []): Collection;

    public function createShelfSpotAdmin(array $attributes): User;

    public function updateShelfSpotAdmin(User $user, array $attributes): User;

    public function deleteShelfSpotAdmin(User $user): void;
}
