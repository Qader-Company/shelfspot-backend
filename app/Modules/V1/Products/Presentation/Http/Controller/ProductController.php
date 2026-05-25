<?php

namespace App\Modules\V1\Products\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Products\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\V1\Products\Presentation\Http\Requests\StoreProductRequest;
use App\Modules\V1\Products\Presentation\Http\Requests\UpdateProductRequest;
use App\Modules\V1\Products\Presentation\Http\Resources\ProductResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductController extends Controller
{
    use Filterable;

    public function __construct(private readonly ProductRepositoryInterface $productRepository)
    {
    }

    public function index()
    {
        $filters = $this->acceptedFilters(
            request(),
            ['name', 'is_active', 'brand_id', 'sub_brand_id', 'category_id', 'sub_category_id']
        );
        $products = $this->productRepository->getAll(
            relations: ['media'],
            filters: $filters
        );

        return ApiResponse::success(ProductResource::collection($products)->response()->getData(true));
    }

    public function show(string $id)
    {
        return ApiResponse::success(new ProductResource($this->getProduct($id)));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $this->productRepository->create($data, $data['image'] ?? null);
        return ApiResponse::message(__('apiMessage.created'));
    }

    public function update(UpdateProductRequest $request, string $id)
    {
        $data = $request->validated();
        $this->productRepository->update($this->getProduct($id), $data, $data['image'] ?? null);
        return ApiResponse::message(__('apiMessage.updated'));
    }

    public function destroy(string $id)
    {
        $this->productRepository->delete($this->getProduct($id));
        return ApiResponse::deleted();
    }

    private function getProduct(string $id)
    {
        $product = $this->productRepository->getById($id, ['media']);
        if (is_null($product)) {
            throw new ModelNotFoundException(__('products.not_found'));
        }

        return $product;
    }
}
