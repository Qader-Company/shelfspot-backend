<?php

namespace App\Modules\V1\AccessControl\Domain\Repositories;

use App\Modules\V1\AccessControl\Domain\Models\Role;
use Illuminate\Database\Eloquent\Collection;

interface AccessControlRepositoryInterface
{
    public function permissions(string $portal, ?int $companyId = null): Collection;
    public function roles(string $portal, ?int $companyId = null): Collection;
    public function createRole(string $portal, ?int $companyId, array $attributes): Role;
    public function updateRole(string $portal, ?int $companyId, int $roleId, array $attributes): Role;
    public function deleteRole(string $portal, ?int $companyId, int $roleId): void;
    public function scopedRolesByNames(string $portal, ?int $companyId, array $names): Collection;
}
