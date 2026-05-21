<?php

namespace App\Modules\V1\Users\Domain\Repositories;

use App\Modules\V1\Users\Domain\Models\User;

interface UserRepositoryInterface
{
    public function list(bool $active, array $filters);

    public function findById(int $id): User;

    public function create(array $attributes): User;
    public function update(User $user, array $attributes): User;

    public function delete(User $user): void;

    public function findWhere(array $attributes);
}
