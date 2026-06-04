<?php

namespace App\Modules\V1\Categories\Domain\Repositories;

use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\V1\Categories\Domain\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;

interface CategoryRepositoryInterface extends TrashableRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator;
    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Category;
    public function create(array $attributes): Category;
    public function update(Category $category, array $attributes): Category;
    public function delete(Category $category): void;
}
