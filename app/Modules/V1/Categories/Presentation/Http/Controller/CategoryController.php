<?php

namespace App\Modules\V1\Categories\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Presentation\Http\Requests\ImportExcelRequest;
use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\Shared\Presentation\Http\Controllers\ManagesTrash;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Categories\Application\Services\CategoryExcelService;
use App\Modules\V1\Categories\Domain\Repositories\CategoryRepositoryInterface;
use App\Modules\V1\Categories\Presentation\Http\Requests\StoreCategoryRequest;
use App\Modules\V1\Categories\Presentation\Http\Requests\UpdateCategoryRequest;
use App\Modules\V1\Categories\Presentation\Http\Resources\CategoryResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    use Filterable, ManagesTrash;

    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryExcelService $categoryExcelService,
    )
    {
    }

    public function index()
    {
        $filters = $this->acceptedFilters(
            request(), ['name', 'active', 'brand_id', 'sub_brand_id']
        );
        $categories = $this->categoryRepository->getAll(
            relations: ['media', 'translations', 'brand.translations', 'subBrand.translations'],
            filters: $filters,
        );
        return ApiResponse::success(
            CategoryResource::collection($categories)
                ->response()
                ->getData(true)
        );
    }

    public function show(string $id)
    {
        return ApiResponse::success(new CategoryResource($this->getCategory($id, ['media', 'translations', 'brand.translations', 'subBrand.translations'])));
    }

    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();
        $this->categoryRepository->create($data, $data['image'] ?? null);
        return ApiResponse::message(__('api.created'));
    }

    public function update(UpdateCategoryRequest $request, string $id)
    {
        $data = $request->validated();
        $this->categoryRepository->update($this->getCategory($id), $data, $data['image'] ?? null);
        return ApiResponse::message(__('api.updated'));
    }

    public function destroy(string $id)
    {
        $this->categoryRepository->delete($this->getCategory($id));
        return ApiResponse::message(__('api.delete_queued'), Response::HTTP_ACCEPTED);
    }


    public function excelTemplate(): BinaryFileResponse
    {
        return $this->categoryExcelService->template();
    }

    public function excelExport(): BinaryFileResponse
    {
        return $this->categoryExcelService->export();
    }

    public function excelImport(ImportExcelRequest $request)
    {
        $result = $this->categoryExcelService->import($request->file('file'));
        $message = $result->hasErrors()
            ? __('Imported with row-level validation errors. Please review the errors array.')
            : __('Imported successfully.');

        return ApiResponse::success($result->toArray(), $message);
    }

    protected function trashRepository(): TrashableRepositoryInterface
    {
        return $this->categoryRepository;
    }

    protected function trashResourceCollection(LengthAwarePaginator $items): mixed
    {
        return CategoryResource::collection($items)->response()->getData(true);
    }

    private function getCategory(string $id, array $relations = ['media'])
    {
        $category = $this->categoryRepository->getById($id, $relations);
        if (is_null($category)) {
            throw new ModelNotFoundException(__('categories.not_found'));
        }

        return $category;
    }
}
