<?php

namespace App\Modules\V1\Brands\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Brands\Application\UseCases\DeleteBrandUseCase;
use App\Modules\V1\Brands\Application\UseCases\UpdateBrandUseCase;
use App\Modules\V1\Brands\Domain\Repositories\BrandRepositoryInterface;
use App\Modules\V1\Brands\Presentation\Http\Requests\StoreBrandRequest;
use App\Modules\V1\Brands\Presentation\Http\Requests\UpdateBrandRequest;
use App\Modules\V1\Brands\Presentation\Http\Resources\BrandResource;

class BrandController extends Controller
{
    use Filterable;

    public function __construct(
        private readonly BrandRepositoryInterface $brandRepository,
        private readonly TenantContextInterface $tenantContext
    )
    {
    }

    public function index()
    {
        $filters = $this->acceptedFilters(
            request(), ['name', 'active']
        );
        $brands = $this->brandRepository->getAll(
            relations: ['media'],
            filters: $filters
        );

        return ApiResponse::success(
            BrandResource::collection($brands)->response()->getData(true)
        );
    }

    public function show(string $id)
    {
        $brand = $this->brandRepository->getById($id, ['media']);
        return ApiResponse::success(new BrandResource($brand));
    }

    public function store(StoreBrandRequest $request)
    {
        $data = $request->validated();
        $this->brandRepository->create($data, $data['logo']);
        return ApiResponse::message(__('apiMessage.created'));
    }

    public function update(UpdateBrandRequest $request, string $id, UpdateBrandUseCase $updateBrandUseCase)
    {
        $attributes = $request->validated();
        $updateBrandUseCase->execute($id, $attributes);
        return ApiResponse::message(__('apiMessage.updated'));
    }

    public function destroy(string $id, DeleteBrandUseCase $deleteBrandUseCase)
    {
        $deleteBrandUseCase->execute($id);
        return ApiResponse::deleted();
    }

}
