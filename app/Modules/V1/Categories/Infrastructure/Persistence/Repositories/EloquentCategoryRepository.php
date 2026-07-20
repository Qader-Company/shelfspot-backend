<?php

namespace App\Modules\V1\Categories\Infrastructure\Persistence\Repositories;

use App\Modules\Shared\Support\Traits\HasTranslation;
use App\Modules\Shared\Infrastructure\Persistence\Repositories\CascadesCatalogTrashActions;
use App\Modules\Shared\Domain\ValueObjects\SingleMediaUpdateActionEnum;
use App\Modules\V1\Categories\Domain\Models\Category;
use App\Modules\V1\Categories\Domain\Repositories\CategoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    use CascadesCatalogTrashActions, HasTranslation;

    protected function trashableModel(): string
    {
        return Category::class;
    }

    protected function trashCascadeRelations(): array
    {
        return ['subCategories', 'products'];
    }
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->query($relations, $relationsCount, $filters)
            ->paginate();
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Category
    {
        return $this->query($relations, $relationsCount)->find($id);
    }

    public function create(array $attributes, UploadedFile $image = null): Category
    {
        return DB::transaction(function () use ($attributes, $image) {
            $translations = $attributes['translations'] ?? [];

            unset($attributes['translations']);

            $category = Category::create($attributes);
            $this->fillTranslations($category, $translations);
            $category->save();
            if ($image) {
                $category->addMedia($image)->toMediaCollection('image');
            }
            return $category;
        });
    }

    public function update(Category $category, array $attributes, UploadedFile $image = null, ?SingleMediaUpdateActionEnum $imageAction = null): Category
    {
        return DB::transaction(function () use ($category, $attributes, $image, $imageAction) {
            $translations = $attributes['translations'] ?? [];

            unset($attributes['translations']);

            $category->update($attributes);
            $this->fillTranslations($category, $translations);
            $category->save();
            if ($imageAction === SingleMediaUpdateActionEnum::REMOVE) {
                $category->clearMediaCollection('image');
            } elseif ($image) {
                $category->clearMediaCollection('image');
                $category->addMedia($image)->toMediaCollection('image');
            }
            return $category;
        });
    }

    public function delete(Category $category): void
    {
        $this->deleteWithCatalogChildren($category);
    }

    private function query(array $relations, array $relationsCount, array $filters = [])
    {
        return Category::query()
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
