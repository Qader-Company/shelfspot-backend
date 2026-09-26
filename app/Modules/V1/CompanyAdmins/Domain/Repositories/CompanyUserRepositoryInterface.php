<?php

namespace App\Modules\V1\CompanyAdmins\Domain\Repositories;

use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface CompanyUserRepositoryInterface
{
    public function users(int $companyId, array $filters = []): Collection;

    public function findUser(int $companyId, int $userId): User;

    public function profileFor(int $companyId, User $user): CompanyUser;

    public function create(array $attributes): CompanyUser;

    public function update(CompanyUser $companyUser, array $attributes): CompanyUser;

    public function delete(CompanyUser $companyUser): void;
}
