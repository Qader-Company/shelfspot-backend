<?php

namespace App\Modules\V1\SubCategories\Domain\Repositories;

use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\Shared\Domain\ValueObjects\SingleMediaUpdateActionEnum;
use App\Modules\V1\SubCategories\Domain\Models\SubCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface SubCategoryRepositoryInterface extends TrashableRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator;
    public function getById(int $id, array $relations = [], array $relationsCount = []): ?SubCategory;
    public function create(array $attributes, UploadedFile $image = null): SubCategory;
    public function update(SubCategory $subCategory, array $attributes, UploadedFile $image = null, ?SingleMediaUpdateActionEnum $imageAction = null): SubCategory;
    public function delete(SubCategory $subCategory): void;
}
