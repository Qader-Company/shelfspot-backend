<?php

namespace App\Modules\V1\CompanyUsers\Domain\Repositories;

use App\Modules\V1\CompanyUsers\Domain\Models\CompanyUser;

interface CompanyUserRepositoryInterface
{
    public function list(bool $active, array $filters);

    public function findById(int $id): CompanyUser;

    public function create(array $attributes): CompanyUser;

    public function update(CompanyUser $course, array $attributes): CompanyUser;

    public function delete(CompanyUser $course): void;
}
