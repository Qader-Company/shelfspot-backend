<?php

namespace App\Modules\V1\CompanyAdmins\Infrastructure\Persistence\Repositories;

use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;
use App\Modules\V1\CompanyAdmins\Domain\Repositories\CompanyAdminRepositoryInterface;

class EloquentCompanyUserRepository implements CompanyAdminRepositoryInterface
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
