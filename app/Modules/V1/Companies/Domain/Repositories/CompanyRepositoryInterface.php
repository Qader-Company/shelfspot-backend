<?php

namespace App\Modules\V1\Companies\Domain\Repositories;

use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\V1\Companies\Domain\Models\Company;
use Illuminate\Pagination\LengthAwarePaginator;

interface CompanyRepositoryInterface extends TrashableRepositoryInterface
{
    public function getAll(
        array $relations = [],
        array $relationsCount = [],
        array $filters = [],
    ): LengthAwarePaginator;

    public function getById(
        int $id,
        array $relations = [],
        array $relationsCount = [],
    ): ?Company;

    public function create(array $attributes): Company;
    public function update(Company $company, array $attributes): Company;
    public function delete(Company $company);
}
