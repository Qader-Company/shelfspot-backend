<?php

namespace App\Modules\V1\Brands\Infrastructure\Persistence\Repositories;

use App\Models\Scopes\CompanyScope;
use App\Modules\V1\Brands\Domain\Models\Brand;
use App\Modules\V1\Brands\Domain\Repositories\{BrandRepositoryInterface};
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EloquentBrandRepository implements BrandRepositoryInterface
{
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
        )->paginate(request('per_page', 15));
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
        ->paginate(request('per_page', 15));
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
        $brand->clearMediaCollection('logo');
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
