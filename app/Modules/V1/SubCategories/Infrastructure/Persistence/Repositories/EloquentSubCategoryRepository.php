<?php

namespace App\Modules\V1\SubCategories\Infrastructure\Persistence\Repositories;

use App\Modules\Shared\Infrastructure\Persistence\Repositories\CascadesCatalogTrashActions;
use App\Modules\V1\SubCategories\Domain\Models\SubCategory;
use App\Modules\V1\SubCategories\Domain\Repositories\SubCategoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EloquentSubCategoryRepository implements SubCategoryRepositoryInterface
{
    use CascadesCatalogTrashActions;

    protected function trashableModel(): string
    {
        return SubCategory::class;
    }

    protected function trashCascadeRelations(): array
    {
        return ['products'];
    }
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->query($relations, $relationsCount, $filters)->paginate();
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?SubCategory
    {
        return $this->query($relations, $relationsCount)->find($id);
    }

    public function create(array $attributes, UploadedFile $image = null): SubCategory
    {
        return DB::transaction(function () use ($attributes, $image) {
            $subCategory = SubCategory::create($attributes);
            if ($image) {
                $subCategory->addMedia($image)->toMediaCollection('image');
            }
            return $subCategory;
        });
    }

    public function update(SubCategory $subCategory, array $attributes, UploadedFile $image = null): SubCategory
    {
        return DB::transaction(function () use ($subCategory, $attributes, $image) {
            $subCategory->update($attributes);
            if ($image) {
                $subCategory->clearMediaCollection('image');
                $subCategory->addMedia($image)->toMediaCollection('image');
            }
            return $subCategory;
        });
    }

    public function delete(SubCategory $subCategory): void
    {
        $this->deleteWithCatalogChildren($subCategory);
    }

    private function query(array $relations, array $relationsCount, array $filters = [])
    {
        return SubCategory::query()
            ->when(
                $filters,
                fn($q) => $q->filter($filters)
            )
            ->when(
                $relations,
                fn($q) => $q->with($relations)
            )
            ->when(
                $relationsCount,
                fn($q) => $q->withCount($relationsCount)
            );
    }
}
