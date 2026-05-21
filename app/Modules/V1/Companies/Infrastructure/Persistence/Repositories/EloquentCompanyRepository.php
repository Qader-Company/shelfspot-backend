<?php

namespace App\Modules\V1\Companies\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Companies\Domain\Repositories\CompanyRepositoryInterface;

class EloquentCompanyRepository implements CompanyRepositoryInterface
{
    public function list(bool $active, array $filters)
    {
        // TODO: Implement list() method.
    }

    public function findById(int $id): Company
    {
        // TODO: Implement findById() method.
    }

    public function create(array $attributes): Company
    {
        return Company::create($attributes);
    }

    public function update(Company $course, array $attributes): Company
    {
        // TODO: Implement update() method.
    }

    public function delete(Company $course): void
    {
        // TODO: Implement delete() method.
    }
}
