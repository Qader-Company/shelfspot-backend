<?php
namespace App\Modules\V1\SubBrands\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\SubBrands\Domain\Repositories\SubBrandRepositoryInterface;
use App\Modules\V1\SubBrands\Presentation\Http\Requests\StoreSubBrandRequest;
use App\Modules\V1\SubBrands\Presentation\Http\Requests\UpdateSubBrandRequest;
use App\Modules\V1\SubBrands\Presentation\Http\Resources\SubBrandResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SubBrandController extends Controller
{
    use Filterable;

    public function __construct(private readonly SubBrandRepositoryInterface $subBrandRepository) {}

    public function index()
    {
        $filters = $this->acceptedFilters(
            request(), ['name', 'is_active', 'brand_id']
        );
        $subBrands = $this->subBrandRepository->getAll(
            relations: ['media'],
            filters: $filters
        );
        return ApiResponse::success(
            SubBrandResource::collection($subBrands)
                ->response()
                ->getData(true)
        );
    }

    public function show(string $id)
    {
        $subBrand = $this->getSubBrand($id);
        return ApiResponse::success(new SubBrandResource($subBrand));
    }
    public function store(StoreSubBrandRequest $request)
    {
        $data = $request->validated();
        $this->subBrandRepository->create($data, $data['logo']);
        return ApiResponse::message(__('apiMessage.created'));
    }
    public function update(UpdateSubBrandRequest $request, string $id)
    {
        $data = $request->validated();
        $subBrand = $this->getSubBrand($id);
        $this->subBrandRepository->update($subBrand, $data, $data['logo'] ?? null);
        return ApiResponse::message(__('apiMessage.updated'));
    }
    public function destroy(string $id)
    {
        $subBrand = $this->getSubBrand($id);
        $this->subBrandRepository->delete($subBrand);
        return ApiResponse::deleted();
    }

    private function getSubBrand(string $id)
    {
        $subBrand = $this->subBrandRepository->getById($id);
        if (is_null($subBrand)) {
            throw new ModelNotFoundException(__('subBrands.not_found'));
        }
        return $subBrand;
    }
}
