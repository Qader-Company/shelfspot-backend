<?php

namespace App\Modules\V1\SubCategories\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\SubCategories\Domain\Repositories\SubCategoryRepositoryInterface;
use App\Modules\V1\SubCategories\Presentation\Http\Requests\StoreSubCategoryRequest;
use App\Modules\V1\SubCategories\Presentation\Http\Requests\UpdateSubCategoryRequest;
use App\Modules\V1\SubCategories\Presentation\Http\Resources\SubCategoryResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SubCategoryController extends Controller
{
    use Filterable;

    public function __construct(private readonly SubCategoryRepositoryInterface $subCategoryRepository)
    {
    }

    public function index()
    {
        $filters = $this->acceptedFilters(request(), ['name', 'is_active', 'brand_id', 'sub_brand_id', 'category_id']);
        $subCategories = $this->subCategoryRepository->getAll(relations: ['media'], filters: $filters);

        return ApiResponse::success(SubCategoryResource::collection($subCategories)->response()->getData(true));
    }

    public function show(string $id)
    {
        return ApiResponse::success(new SubCategoryResource($this->getSubCategory($id)));
    }

    public function store(StoreSubCategoryRequest $request)
    {
        $data = $request->validated();
        $this->subCategoryRepository->create($data, $data['image'] ?? null);
        return ApiResponse::message(__('apiMessage.created'));
    }

    public function update(UpdateSubCategoryRequest $request, string $id)
    {
        $data = $request->validated();
        $this->subCategoryRepository->update($this->getSubCategory($id), $data, $data['image'] ?? null);
        return ApiResponse::message(__('apiMessage.updated'));
    }

    public function destroy(string $id)
    {
        $this->subCategoryRepository->delete($this->getSubCategory($id));
        return ApiResponse::deleted();
    }

    private function getSubCategory(string $id)
    {
        $subCategory = $this->subCategoryRepository->getById($id, ['media']);
        if (is_null($subCategory)) {
            throw new ModelNotFoundException(__('subCategories.not_found'));
        }

        return $subCategory;
    }
}
