<?php

namespace App\Modules\V1\Products\Domain\Repositories;

use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\V1\Products\Domain\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface ProductRepositoryInterface extends TrashableRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator;
    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Product;
    public function create(array $attributes, UploadedFile $image = null): Product;
    public function update(Product $product, array $attributes, UploadedFile $image = null): Product;
    public function delete(Product $product): void;
}
