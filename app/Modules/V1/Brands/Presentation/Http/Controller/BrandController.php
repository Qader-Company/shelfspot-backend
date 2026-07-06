<?php

namespace App\Modules\V1\Brands\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Presentation\Http\Requests\ImportExcelRequest;
use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\Shared\Presentation\Http\Controllers\ManagesTrash;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Brands\Application\Services\BrandExcelService;
use App\Modules\V1\Brands\Domain\Repositories\BrandRepositoryInterface;
use App\Modules\V1\Brands\Presentation\Http\Requests\StoreBrandRequest;
use App\Modules\V1\Brands\Presentation\Http\Requests\UpdateBrandRequest;
use App\Modules\V1\Brands\Presentation\Http\Resources\BrandResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class BrandController extends Controller
{
    use Filterable, ManagesTrash;

    public function __construct(
        private readonly BrandRepositoryInterface $brandRepository,
        private readonly BrandExcelService $brandExcelService,
    )
    {
    }

    public function index()
    {
        $filters = $this->acceptedFilters(
            request(), ['name', 'active']
        );
        $brands = $this->brandRepository->getAll(
            relations: ['media', 'translations'],
            filters: $filters
        );

        return ApiResponse::success(
            BrandResource::collection($brands)->response()->getData(true)
        );
    }

    public function show(string $id)
    {
        $brand = $this->getBrand($id, ['media', 'translations']);
        return ApiResponse::success(new BrandResource($brand));
    }

    public function store(StoreBrandRequest $request)
    {
        $data = $request->validated();
        $this->brandRepository->create($data, $data['logo'] ?? null);
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
        return ApiResponse::message(__('api.delete_queued'), Response::HTTP_ACCEPTED);
    }


    public function excelTemplate(): BinaryFileResponse
    {
        return $this->brandExcelService->template();
    }

    public function excelExport(): BinaryFileResponse
    {
        return $this->brandExcelService->export();
    }

    public function excelImport(ImportExcelRequest $request)
    {
        $result = $this->brandExcelService->import($request->file('file'));
        $message = $result->hasErrors()
            ? __('Imported with row-level validation errors. Please review the errors array.')
            : __('Imported successfully.');

        return ApiResponse::success($result->toArray(), $message);
    }

    protected function trashRepository(): TrashableRepositoryInterface
    {
        return $this->brandRepository;
    }

    protected function trashResourceCollection(LengthAwarePaginator $items): mixed
    {
        return BrandResource::collection($items)->response()->getData(true);
    }

    private function getBrand(string $id, $relations = [], $relationsCount = [])
    {
        $brand = $this->brandRepository->getById($id, $relations, $relationsCount);
        if(is_null($brand))
            throw new ModelNotFoundException(__('brands.not_found'));
        return $brand;
    }
}
