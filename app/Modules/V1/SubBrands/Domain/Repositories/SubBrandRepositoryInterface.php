<?php
namespace App\Modules\V1\SubBrands\Domain\Repositories;

use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\V1\SubBrands\Domain\Models\SubBrand;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface SubBrandRepositoryInterface extends TrashableRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator;
    public function getById(int $id, array $relations = [], array $relationsCount = []): ?SubBrand;
    public function create(array $attributes, UploadedFile $logo = null): SubBrand;
    public function update(SubBrand $subBrand, array $attributes, UploadedFile $logo = null): SubBrand;
    public function delete(SubBrand $subBrand): void;
}
