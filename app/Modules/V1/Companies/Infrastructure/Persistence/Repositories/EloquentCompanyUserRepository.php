<?php

namespace App\Modules\V1\Companies\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Companies\Domain\Models\CompanyUser;
use App\Modules\V1\Companies\Domain\Repositories\CompanyUserRepositoryInterface;

class EloquentCompanyUserRepository implements CompanyUserRepositoryInterface
{
    public function list(bool $active, array $filters)
    {
        // TODO: Implement list() method.
    }

    public function findById(int $id): CompanyUser
    {
        // TODO: Implement findById() method.
    }

    public function create(array $attributes): CompanyUser
    {
        return CompanyUser::create($attributes);
    }

    public function update(CompanyUser $course, array $attributes): CompanyUser
    {
        // TODO: Implement update() method.
    }

    public function delete(CompanyUser $course): void
    {
        // TODO: Implement delete() method.
    }
}
