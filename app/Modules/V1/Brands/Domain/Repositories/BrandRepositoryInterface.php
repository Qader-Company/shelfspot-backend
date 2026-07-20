<?php

namespace App\Modules\V1\Brands\Domain\Repositories;

use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\Shared\Domain\ValueObjects\SingleMediaUpdateActionEnum;
use App\Modules\V1\Brands\Domain\Models\Brand;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface BrandRepositoryInterface extends TrashableRepositoryInterface
{
    public function getAll(
        array $relations = [],
        array $relationsCount = [],
        array $filters = [],
    ): LengthAwarePaginator;

    public function getByCompanyId(
        int $companyId,
        array $relations = [],
        array $relationsCount = [],
        array $filters = [],
    ): LengthAwarePaginator;

    public function getById(
        int $id,
        array $relations = [],
        array $relationsCount = [],
        bool $global = false
    ): ?Brand;

    public function create(array $attributes, UploadedFile $logo = null): Brand;
    public function update(Brand $brand, array $attributes, UploadedFile $logo = null, ?SingleMediaUpdateActionEnum $logoAction = null): Brand;
    public function delete(Brand $brand);
}
