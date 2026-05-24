<?php

namespace App\Modules\V1\Brands\Domain\Repositories;

use App\Modules\V1\Brands\Domain\Models\Brand;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface BrandRepositoryInterface
{
    public function getByCompanyId(
        int $companyId,
        array $relations = [],
        array $relationsCount = [],
        array $filters = [],
        bool $global = false
    ): LengthAwarePaginator;
    public function getById(
        int $id,
        array $relations = [],
        array $relationsCount = []
    ): ?Brand;
    public function create(array $attributes, UploadedFile $logo = null): Brand;
    public function update(Brand $brand, array $attributes, UploadedFile $logo = null): Brand;
    public function delete(Brand $brand);
}
