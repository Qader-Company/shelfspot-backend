<?php

namespace App\Modules\V1\Brands\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Brands\Domain\Repositories\BrandRepositoryInterface;
use App\Modules\V1\Brands\Presentation\Http\Requests\StoreBrandRequest;
use App\Modules\V1\Brands\Presentation\Http\Requests\UpdateBrandRequest;
use App\Modules\V1\Brands\Presentation\Http\Resources\BrandResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BrandController extends Controller
{
    use Filterable;

    public function __construct(
        private readonly BrandRepositoryInterface $brandRepository
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
        $brand = $this->getBrand($id);
        return ApiResponse::success(new BrandResource($brand));
    }

    public function store(StoreBrandRequest $request)
    {
        $data = $request->validated();
        $this->brandRepository->create($data, $data['logo']);
        return ApiResponse::message(__('api.created'));
    }

    public function update(UpdateBrandRequest $request, string $id)
    {
        $attributes = $request->validated();
        $brand = $this->getBrand($id);
        $this->brandRepository->update(
            $brand,
            $attributes,
            logo: $attributes['logo'] ?? null
        );
        return ApiResponse::message(__('api.updated'));
    }

    public function destroy(string $id)
    {
        $brand = $this->getBrand($id);
        $this->brandRepository->delete($brand);
        return ApiResponse::deleted();
    }

    private function getBrand(string $id, $relations = [], $relationsCount = [])
    {
        $brand = $this->brandRepository->getById($id, $relations, $relationsCount);
        if(is_null($brand))
            throw new ModelNotFoundException(__('brands.not_found'));
        return $brand;
    }
}
