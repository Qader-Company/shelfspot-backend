<?php

namespace App\Modules\V1\Products\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\Products\Domain\Repositories\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->query($relations, $relationsCount, $filters)->paginate(request('per_page', 15));
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Product
    {
        return $this->query($relations, $relationsCount)->find($id);
    }

    public function create(array $attributes, UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($attributes, $image) {
            $product = Product::create($attributes);
            if ($image) {
                $product->addMedia($image)->toMediaCollection('image');
            }
            return $product;
        });
    }

    public function update(Product $product, array $attributes, UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($product, $attributes, $image) {
            $product->update($attributes);
            if ($image) {
                $product->clearMediaCollection('image');
                $product->addMedia($image)->toMediaCollection('image');
            }
            return $product;
        });
    }

    public function delete(Product $product): void
    {
        $product->clearMediaCollection('image');
        $product->delete();
    }

    private function query(array $relations, array $relationsCount, array $filters = [])
    {
        return Product::query()
            ->when($filters, fn($q) => $q->filter($filters))
            ->when($relations, fn($q) => $q->with($relations))
            ->when($relationsCount, fn($q) => $q->withCount($relationsCount));
    }
}
