<?php

namespace App\Modules\V1\Categories\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Categories\Domain\Models\Category;
use App\Modules\V1\Categories\Domain\Repositories\CategoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->query($relations, $relationsCount, $filters)->paginate(request('per_page', 15));
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Category
    {
        return $this->query($relations, $relationsCount)->find($id);
    }

    public function create(array $attributes): Category
    {
        return Category::create($attributes);
    }

    public function update(Category $category, array $attributes): Category
    {
        $category->update($attributes);
        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    private function query(array $relations, array $relationsCount, array $filters = [])
    {
        return Category::when($filters, fn($q) => $q->filter($filters))
            ->when($relations, fn($q) => $q->with($relations))
            ->when($relationsCount, fn($q) => $q->withCount($relationsCount));
    }
}
