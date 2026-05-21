<?php

namespace App\Modules\V1\Companies\Domain\Repositories;

use App\Modules\V1\Companies\Domain\Models\Company;

interface CompanyRepositoryInterface
{
    public function list(bool $active, array $filters);

    public function findById(int $id): Company;

    public function create(array $attributes): Company;

    public function update(Company $course, array $attributes): Company;

    public function delete(Company $course): void;
}
