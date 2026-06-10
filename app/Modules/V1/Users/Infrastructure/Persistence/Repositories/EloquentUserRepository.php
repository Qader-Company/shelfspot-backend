<?php

namespace App\Modules\V1\Users\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function list(bool $active, array $filters)
    {
        // TODO: Implement list() method.
    }

    public function findById(int $id): User
    {
        // TODO: Implement findById() method.
    }

    public function create(array $attributes): User
    {
        return User::Create($attributes);
    }

    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);
        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function findWhere(array $attributes)
    {
        return User::where($attributes)->first();
    }
}
