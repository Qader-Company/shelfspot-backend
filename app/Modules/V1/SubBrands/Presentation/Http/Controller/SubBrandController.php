<?php
namespace App\Modules\V1\SubBrands\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Presentation\Http\Requests\ImportExcelRequest;
use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\Shared\Presentation\Http\Controllers\ManagesTrash;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\SubBrands\Application\Services\SubBrandExcelService;
use App\Modules\V1\SubBrands\Domain\Repositories\SubBrandRepositoryInterface;
use App\Modules\V1\SubBrands\Presentation\Http\Requests\StoreSubBrandRequest;
use App\Modules\V1\SubBrands\Presentation\Http\Requests\UpdateSubBrandRequest;
use App\Modules\V1\SubBrands\Presentation\Http\Resources\SubBrandResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class SubBrandController extends Controller
{
    use Filterable, ManagesTrash;

    public function __construct(
        private readonly SubBrandRepositoryInterface $subBrandRepository,
        private readonly SubBrandExcelService $subBrandExcelService,
    ) {}

    public function index()
    {
        $filters = $this->acceptedFilters(
            request(), ['name', 'active', 'brand_id']
        );
        $subBrands = $this->subBrandRepository->getAll(
            relations: ['media','brand'],
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
        return ApiResponse::message(__('api.created'));
    }
    public function update(UpdateSubBrandRequest $request, string $id)
    {
        $data = $request->validated();
        $subBrand = $this->getSubBrand($id);
        $this->subBrandRepository->update($subBrand, $data, $data['logo'] ?? null);
        return ApiResponse::message(__('api.updated'));
    }
    public function destroy(string $id)
    {
        $subBrand = $this->getSubBrand($id);
        $this->subBrandRepository->delete($subBrand);
        return ApiResponse::message(__('api.delete_queued'), Response::HTTP_ACCEPTED);
    }

    public function excelTemplate(): BinaryFileResponse
    {
        return $this->subBrandExcelService->template();
    }

    public function excelExport(): BinaryFileResponse
    {
        return $this->subBrandExcelService->export();
    }

    public function excelImport(ImportExcelRequest $request)
    {
        $result = $this->subBrandExcelService->import($request->file('file'));
        $message = $result->hasErrors()
            ? __('Imported with row-level validation errors. Please review the errors array.')
            : __('Imported successfully.');

        return ApiResponse::success($result->toArray(), $message);
    }

    protected function trashRepository(): TrashableRepositoryInterface
    {
        return $this->subBrandRepository;
    }

    protected function trashResourceCollection(LengthAwarePaginator $items): mixed
    {
        return SubBrandResource::collection($items)->response()->getData(true);
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
