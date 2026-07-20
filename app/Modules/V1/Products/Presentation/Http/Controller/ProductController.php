<?php

namespace App\Modules\V1\Products\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Presentation\Http\Requests\ImportExcelRequest;
use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\Shared\Presentation\Http\Controllers\ManagesTrash;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Products\Application\Services\ProductExcelService;
use App\Modules\V1\Products\Application\Services\ProductFilterOptionsService;
use App\Modules\V1\Products\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\V1\Products\Presentation\Http\Requests\ProductFilterOptionsRequest;
use App\Modules\V1\Products\Presentation\Http\Requests\StoreProductRequest;
use App\Modules\V1\Products\Presentation\Http\Requests\UpdateProductRequest;
use App\Modules\V1\Products\Presentation\Http\Resources\ProductResource;
use App\Modules\Shared\Domain\ValueObjects\SingleMediaUpdateActionEnum;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    use Filterable, ManagesTrash;

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductExcelService $productExcelService,
    ) {
    }

    public function index()
    {
        $filters = $this->acceptedFilters(
            request(),
            ['search', 'active', 'brand_id', 'sub_brand_id', 'category_id', 'sub_category_id']
        );
        $products = $this->productRepository->getAll(
            relations: ['media', 'translations', 'brand.translations', 'subBrand.translations', 'category.translations', 'subCategory.translations'],
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
            ['media', 'translations', 'brand.translations', 'subBrand.translations', 'category.translations', 'subCategory.translations']
        );
        return ApiResponse::success(new ProductResource($product));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $product = $this->productRepository->create(
            $data,
            image: $data['image'] ?? null
        );

        return ApiResponse::created(
            new ProductResource($product->load(['media', 'translations', 'brand.translations', 'subBrand.translations', 'category.translations', 'subCategory.translations']))
        );
    }

    public function update(UpdateProductRequest $request, string $id)
    {
        $data = $request->validated();
        $product = $this->productRepository->update(
            $this->getProduct($id),
            Arr::except($data, ['image', 'image_action']),
            image: $data['image'] ?? null,
            imageAction: isset($data['image_action']) ? SingleMediaUpdateActionEnum::from($data['image_action']) : null,
        );

        return ApiResponse::updated(
            new ProductResource($product->refresh()->load(['media', 'translations', 'brand.translations', 'subBrand.translations', 'category.translations', 'subCategory.translations']))
        );
    }

    public function destroy(string $id)
    {
        $this->productRepository->delete(
            $this->getProduct($id)
        );
        return ApiResponse::deleted();
    }


    public function excelTemplate(): BinaryFileResponse
    {
        return $this->productExcelService->template();
    }

    public function excelExport(): BinaryFileResponse
    {
        return $this->productExcelService->export();
    }

    public function excelImport(ImportExcelRequest $request)
    {
        $result = $this->productExcelService->import($request->file('file'));
        $message = $result->hasErrors()
            ? __('Imported with row-level validation errors. Please review the errors array.')
            : __('Imported successfully.');

        return ApiResponse::success($result->toArray(), $message);
    }

    protected function trashRepository(): TrashableRepositoryInterface
    {
        return $this->productRepository;
    }

    protected function trashResourceCollection(LengthAwarePaginator $items): mixed
    {
        return ProductResource::collection($items)->response()->getData(true);
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
