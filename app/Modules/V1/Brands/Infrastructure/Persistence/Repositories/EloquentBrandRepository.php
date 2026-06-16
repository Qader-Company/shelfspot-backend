<?php

namespace App\Modules\V1\Brands\Infrastructure\Persistence\Repositories;

use App\Modules\Shared\Infrastructure\Persistence\Repositories\HandlesTrash;
use App\Modules\V1\Brands\Domain\Models\Brand;
use App\Modules\V1\Brands\Domain\Repositories\{BrandRepositoryInterface};
use App\Modules\V1\Companies\Domain\Models\Scopes\CompanyScope;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EloquentBrandRepository implements BrandRepositoryInterface
{
    use HandlesTrash;

    protected function trashableModel(): string
    {
        return Brand::class;
    }
    public function getAll(
        array $relations = [],
        array $relationsCount = [],
        array $filters = [],
    ): LengthAwarePaginator
    {
        return $this->query(
            $relations,
            $relationsCount,
            $filters,
        )->paginate();
    }

    public function getByCompanyId(
        int $companyId,
        array $relations = [],
        array $relationsCount = [],
        array $filters = [],
    ): LengthAwarePaginator
    {
        return $this->query(
            $relations,
            $relationsCount,
            $filters,
            true
        )->where('company_id', $companyId)
        ->paginate();
    }

    public function getById(int $id, array $relations = [], array $relationsCount = [], $global = false): ?Brand
    {
        return $this->query(
            relations: $relations,
            relationsCount: $relationsCount,
            global:  $global
        )->find($id);
    }

    public function create(array $attributes, UploadedFile $logo = null): Brand
    {
        return DB::transaction(function () use ($attributes, $logo) {

            $brand = Brand::create($attributes);

            if(!is_null($logo))
                $brand->addMedia($logo)->toMediaCollection('logo');
            return $brand;
        });
    }

    public function update(Brand $brand, array $attributes, UploadedFile $logo = null): Brand
    {
        return DB::transaction(function () use ($brand, $attributes, $logo) {

            $brand->update($attributes);
            if(!is_null($logo)){
                $brand->clearMediaCollection('logo');
                $brand->addMedia($logo)->toMediaCollection('logo');
            }
            return $brand;
        });
    }

    public function delete(Brand $brand): void
    {
        $brand->delete();
    }

    private function query(array $relations, array $relationsCount, array $filters = [], $global = false)
    {
        return Brand::when($global, fn($q) => $q->withoutGlobalScope(CompanyScope::class))
            ->when($filters, fn($q) => $q->filter($filters))
            ->when($relations, fn($q) => $q->with($relations))
            ->when($relationsCount, fn($q) => $q->withCount($relationsCount));
    }
}
