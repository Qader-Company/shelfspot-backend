<?php

namespace App\Modules\V1\Products\Infrastructure\Persistence\Repositories;

use App\Modules\Shared\Support\Traits\HasTranslation;
use App\Modules\Shared\Infrastructure\Persistence\Repositories\HandlesTrash;
use App\Modules\Shared\Domain\ValueObjects\SingleMediaUpdateActionEnum;
use App\Modules\V1\Products\Domain\Models\Product;
use App\Modules\V1\Products\Domain\Repositories\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EloquentProductRepository implements ProductRepositoryInterface
{
    use HandlesTrash, HasTranslation;

    protected function trashableModel(): string
    {
        return Product::class;
    }
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->query($relations, $relationsCount, $filters)->paginate();
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Product
    {
        return $this->query($relations, $relationsCount)->find($id);
    }

    public function create(array $attributes, UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($attributes, $image) {
            $translations = $attributes['translations'] ?? [];

            unset($attributes['translations']);

            $product = Product::create($attributes);
            $this->fillTranslations($product, $translations);
            $product->save();
            if ($image) {
                $product->addMedia($image)->toMediaCollection('image');
            }
            return $product;
        });
    }

    public function update(Product $product, array $attributes, UploadedFile $image = null, ?SingleMediaUpdateActionEnum $imageAction = null): Product
    {
        return DB::transaction(function () use ($product, $attributes, $image, $imageAction) {
            $translations = $attributes['translations'] ?? [];

            unset($attributes['translations']);

            $product->update($attributes);
            $this->fillTranslations($product, $translations);
            $product->save();
            if ($imageAction === SingleMediaUpdateActionEnum::REMOVE) {
                $product->clearMediaCollection('image');
            } elseif ($image) {
                $product->clearMediaCollection('image');
                $product->addMedia($image)->toMediaCollection('image');
            }
            return $product;
        });
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }


    public function countForCompany(int $companyId): int
    {
        return $this->query([], [])
            ->where('company_id', $companyId)
            ->count();
    }

    private function query(array $relations, array $relationsCount, array $filters = [])
    {
        return Product::query()
            ->when($filters, fn($q) => $q->filter($filters))
            ->when($relations, fn($q) => $q->with($relations))
            ->when($relationsCount, fn($q) => $q->withCount($relationsCount));
    }
}
