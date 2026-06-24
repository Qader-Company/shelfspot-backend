<?php

namespace App\Modules\V1\CompanyAdmins\Domain\Repositories;

use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;

interface CompanyAdminRepositoryInterface
{
    public function list(bool $active, array $filters);

    public function findById(int $id): CompanyUser;

    public function create(array $attributes): CompanyUser;

    public function update(CompanyUser $course, array $attributes): CompanyUser;

    public function delete(CompanyUser $course): void;
}
