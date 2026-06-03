<?php

namespace App\Modules\V1\Categories\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Categories\Domain\Repositories\CategoryRepositoryInterface;
use App\Modules\V1\Categories\Presentation\Http\Requests\StoreCategoryRequest;
use App\Modules\V1\Categories\Presentation\Http\Requests\UpdateCategoryRequest;
use App\Modules\V1\Categories\Presentation\Http\Resources\CategoryResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryController extends Controller
{
    use Filterable;

    public function __construct(private readonly CategoryRepositoryInterface $categoryRepository)
    {
    }

    public function index()
    {
        $filters = $this->acceptedFilters(request(), ['name', 'active', 'brand_id', 'sub_brand_id']);
        $categories = $this->categoryRepository->getAll(filters: $filters);

        return ApiResponse::success(CategoryResource::collection($categories)->response()->getData(true));
    }

    public function show(string $id)
    {
        return ApiResponse::success(new CategoryResource($this->getCategory($id)));
    }

    public function store(StoreCategoryRequest $request)
    {
        $this->categoryRepository->create($request->validated());
        return ApiResponse::message(__('api.created'));
    }

    public function update(UpdateCategoryRequest $request, string $id)
    {
        $this->categoryRepository->update($this->getCategory($id), $request->validated());
        return ApiResponse::message(__('api.updated'));
    }

    public function destroy(string $id)
    {
        $this->categoryRepository->delete($this->getCategory($id));
        return ApiResponse::deleted();
    }

    private function getCategory(string $id)
    {
        $category = $this->categoryRepository->getById($id);
        if (is_null($category)) {
            throw new ModelNotFoundException(__('categories.not_found'));
        }

        return $category;
    }
}
