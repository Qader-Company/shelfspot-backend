<?php
namespace App\Modules\V1\SubBrands\Infrastructure\Persistence\Repositories;

use App\Modules\Shared\Support\Traits\HasTranslation;
use App\Modules\Shared\Infrastructure\Persistence\Repositories\CascadesCatalogTrashActions;
use App\Modules\V1\SubBrands\Domain\Models\SubBrand;
use App\Modules\V1\SubBrands\Domain\Repositories\SubBrandRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EloquentSubBrandRepository implements SubBrandRepositoryInterface
{
    use CascadesCatalogTrashActions, HasTranslation;

    protected function trashableModel(): string
    {
        return SubBrand::class;
    }

    protected function trashCascadeRelations(): array
    {
        return ['categories', 'subCategories', 'products'];
    }
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->query($relations, $relationsCount, $filters)->paginate();
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?SubBrand
    {
        return $this->query($relations, $relationsCount)->find($id);
    }

    public function create(array $attributes, UploadedFile $logo = null): SubBrand
    {
        return DB::transaction(function () use ($attributes, $logo) {
            $translations = $attributes['translations'] ?? [];

            unset($attributes['translations']);

            $subBrand = SubBrand::create($attributes);
            $this->fillTranslations($subBrand, $translations);
            $subBrand->save();
            if ($logo) $subBrand->addMedia($logo)->toMediaCollection('logo');
            return $subBrand;
        });
    }

    public function update(SubBrand $subBrand, array $attributes, UploadedFile $logo = null): SubBrand
    {
        return DB::transaction(function () use ($subBrand, $attributes, $logo) {
            $translations = $attributes['translations'] ?? [];

            unset($attributes['translations']);

            $subBrand->update($attributes);
            $this->fillTranslations($subBrand, $translations);
            $subBrand->save();
            if ($logo) {
                $subBrand->clearMediaCollection('logo');
                $subBrand->addMedia($logo)->toMediaCollection('logo');
            }
            return $subBrand;
        });
    }

    public function delete(SubBrand $subBrand): void
    {
        $this->deleteWithCatalogChildren($subBrand);
    }

    private function query(array $relations, array $relationsCount, array $filters = [])
    {
        return SubBrand::query()
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
