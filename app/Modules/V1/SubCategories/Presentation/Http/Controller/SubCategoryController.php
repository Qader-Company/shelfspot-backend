<?php

namespace App\Modules\V1\SubCategories\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Presentation\Http\Requests\ImportExcelRequest;
use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\Shared\Presentation\Http\Controllers\ManagesTrash;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\SubCategories\Application\Services\SubCategoryExcelService;
use App\Modules\V1\SubCategories\Domain\Repositories\SubCategoryRepositoryInterface;
use App\Modules\V1\SubCategories\Presentation\Http\Requests\StoreSubCategoryRequest;
use App\Modules\V1\SubCategories\Presentation\Http\Requests\UpdateSubCategoryRequest;
use App\Modules\V1\SubCategories\Presentation\Http\Resources\SubCategoryResource;
use App\Modules\Shared\Domain\ValueObjects\SingleMediaUpdateActionEnum;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class SubCategoryController extends Controller
{
    use Filterable, ManagesTrash;

    public function __construct(
        private readonly SubCategoryRepositoryInterface $subCategoryRepository,
        private readonly SubCategoryExcelService $subCategoryExcelService,
    )
    {
    }

    public function index()
    {
        $filters = $this->acceptedFilters(request(), ['name', 'active', 'brand_id', 'sub_brand_id', 'category_id']);
        $subCategories = $this->subCategoryRepository->getAll(relations: ['media', 'translations', 'brand.translations', 'subBrand.translations', 'category.translations'], filters: $filters);

        return ApiResponse::success(
            SubCategoryResource::collection($subCategories)
                ->response()
                ->getData(true)
        );
    }

    public function show(string $id)
    {
        $subCategory = $this->getSubCategory(
            $id,
            relations: [
                'media',
                'translations',
                'brand.translations',
                'subBrand.translations',
                'category.translations'
            ]
        );
        return ApiResponse::success(
            new SubCategoryResource($subCategory)
        );
    }

    public function store(StoreSubCategoryRequest $request)
    {
        $data = $request->validated();
        $this->subCategoryRepository->create($data, $data['image'] ?? null);
        return ApiResponse::message(__('api.created'));
    }

    public function update(UpdateSubCategoryRequest $request, string $id)
    {
        $data = $request->validated();
        $this->subCategoryRepository->update(
            $this->getSubCategory($id),
            Arr::except($data, ['image', 'image_action']),
            image: $data['image'] ?? null,
            imageAction: isset($data['image_action']) ? SingleMediaUpdateActionEnum::from($data['image_action']) : null,
        );
        return ApiResponse::message(__('api.updated'));
    }

    public function destroy(string $id)
    {
        $this->subCategoryRepository->delete($this->getSubCategory($id));
        return ApiResponse::message(__('api.delete_queued'), Response::HTTP_ACCEPTED);
    }


    public function excelTemplate(): BinaryFileResponse
    {
        return $this->subCategoryExcelService->template();
    }

    public function excelExport(): BinaryFileResponse
    {
        return $this->subCategoryExcelService->export();
    }

    public function excelImport(ImportExcelRequest $request)
    {
        $result = $this->subCategoryExcelService->import($request->file('file'));
        $message = $result->hasErrors()
            ? __('Imported with row-level validation errors. Please review the errors array.')
            : __('Imported successfully.');

        return ApiResponse::success($result->toArray(), $message);
    }

    protected function trashRepository(): TrashableRepositoryInterface
    {
        return $this->subCategoryRepository;
    }

    protected function trashResourceCollection(LengthAwarePaginator $items): mixed
    {
        return SubCategoryResource::collection($items)->response()->getData(true);
    }

    private function getSubCategory(string $id, array $relations = ['media'])
    {
        $subCategory = $this->subCategoryRepository->getById($id, $relations);
        if (is_null($subCategory)) {
            throw new ModelNotFoundException(__('subCategories.not_found'));
        }

        return $subCategory;
    }
}
