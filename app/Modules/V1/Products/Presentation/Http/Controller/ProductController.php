<?php

namespace App\Modules\V1\Products\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Products\Application\Services\ProductFilterOptionsService;
use App\Modules\V1\Products\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\V1\Products\Presentation\Http\Requests\ProductFilterOptionsRequest;
use App\Modules\V1\Products\Presentation\Http\Requests\StoreProductRequest;
use App\Modules\V1\Products\Presentation\Http\Requests\UpdateProductRequest;
use App\Modules\V1\Products\Presentation\Http\Resources\ProductResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductController extends Controller
{
    use Filterable;

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {
    }

    public function index()
    {
        $filters = $this->acceptedFilters(
            request(),
            ['name', 'is_active', 'brand_id', 'sub_brand_id', 'category_id', 'sub_category_id']
        );
        $products = $this->productRepository->getAll(
            relations: ['media', 'brand', 'subBrand', 'category', 'subCategory'],
            filters: $filters
        );

        return ApiResponse::success(
            ProductResource::collection($products)
                ->response()
                ->getData(true)
        );
    }


    public function filterOptions(ProductFilterOptionsRequest $request, ProductFilterOptionsService $productFilterOptionsService)
    {
        return ApiResponse::success(
            $productFilterOptionsService->resolve(
                $request->validated()
            )
        );
    }

    public function show(string $id)
    {
        $product = $this->getProduct(
            $id,
            ['media', 'brand', 'subBrand', 'category', 'subCategory']
        );
        return ApiResponse::success(new ProductResource($product));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $this->productRepository->create(
            $data,
            image: $data['image'] ?? null
        );
        return ApiResponse::message(__('api.created'));
    }

    public function update(UpdateProductRequest $request, string $id)
    {
        $data = $request->validated();
        $this->productRepository->update(
            $this->getProduct($id),
            $data,
            image: $data['image'] ?? null
        );
        return ApiResponse::message(__('api.updated'));
    }

    public function destroy(string $id)
    {
        $this->productRepository->delete(
            $this->getProduct($id)
        );
        return ApiResponse::deleted();
    }

    private function getProduct(string $id, $relations = [], $relationsCount = [])
    {
        $product = $this->productRepository->getById($id, $relations, $relationsCount);
        if (is_null($product)) {
            throw new ModelNotFoundException(__('products.not_found'));
        }

        return $product;
    }
}
