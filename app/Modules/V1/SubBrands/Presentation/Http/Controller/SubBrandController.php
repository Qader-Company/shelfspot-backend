<?php
namespace App\Modules\V1\SubBrands\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\SubBrands\Domain\Repositories\SubBrandRepositoryInterface;
use App\Modules\V1\SubBrands\Presentation\Http\Requests\StoreSubBrandRequest;
use App\Modules\V1\SubBrands\Presentation\Http\Requests\UpdateSubBrandRequest;
use App\Modules\V1\SubBrands\Presentation\Http\Resources\SubBrandResource;

class SubBrandController extends Controller
{
    use Filterable;

    public function __construct(private readonly SubBrandRepositoryInterface $subBrandRepository) {}

    public function index()
    {
        $filters = $this->acceptedFilters(request(), ['name', 'is_active', 'brand_id']);
        $subBrands = $this->subBrandRepository->getAll(relations: ['media'], filters: $filters);
        return ApiResponse::success(SubBrandResource::collection($subBrands)->response()->getData(true));
    }

    public function show(string $id) { return ApiResponse::success(new SubBrandResource($this->subBrandRepository->getById($id, ['media']))); }
    public function store(StoreSubBrandRequest $request) { $data = $request->validated(); $this->subBrandRepository->create($data, $data['logo']); return ApiResponse::message(__('apiMessage.created')); }
    public function update(UpdateSubBrandRequest $request, string $id) { $m = $this->subBrandRepository->getById($id); $data = $request->validated(); $this->subBrandRepository->update($m, $data, $data['logo'] ?? null); return ApiResponse::message(__('apiMessage.updated')); }
    public function destroy(string $id) { $this->subBrandRepository->delete($this->subBrandRepository->getById($id)); return ApiResponse::deleted(); }
}
