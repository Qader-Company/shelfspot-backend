<?php

namespace App\Modules\V1\Companies\Presentation\Http\Companies;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\Shared\Presentation\Http\Controllers\ManagesTrash;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Companies\Application\UseCases\CreateCompanyWithOwnerUseCase;
use App\Modules\V1\Companies\Domain\Repositories\CompanyRepositoryInterface;
use App\Modules\V1\Companies\Presentation\Http\Requests\RegisterCompanyRequest;
use App\Modules\V1\Companies\Presentation\Http\Requests\UpdateCompanyRequest;
use App\Modules\V1\Companies\Presentation\Http\Resources\CompanyResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    use Filterable, ManagesTrash;

    public function __construct(private readonly CompanyRepositoryInterface $companyRepository)
    {
    }

    public function index(Request $request)
    {
        $filters = $this->acceptedFilters(request(), ['search', 'active', 'industry']);
        $companies = $this->companyRepository->getAll(filters: $filters);
        return ApiResponse::success(
            CompanyResource::collection($companies)
                ->response()
                ->getData(true)
        );
    }
    public function show(string $id)
    {
        $company = $this->getCompany($id, ['users']);
        return ApiResponse::success(new CompanyResource($company));
    }
    public function create(RegisterCompanyRequest $request, CreateCompanyWithOwnerUseCase $createCompanyWithOwnerUseCase)
    {
        $createCompanyWithOwnerUseCase->execute($request->validated());
        return ApiResponse::message(__('api.created'));
    }
    public function update(UpdateCompanyRequest $request, string $id)
    {
        $data = $request->validated();
        $company = $this->getCompany($id);
        $this->companyRepository->update($company, $data);
        return ApiResponse::message(__('api.updated'));
    }
    public function destroy(string $id)
    {
        $company = $this->getCompany($id);
        $this->companyRepository->delete($company);
        return ApiResponse::deleted();
    }

    protected function trashRepository(): TrashableRepositoryInterface
    {
        return $this->companyRepository;
    }

    protected function trashResourceCollection(LengthAwarePaginator $items): mixed
    {
        return CompanyResource::collection($items)->response()->getData(true);
    }

    private function getCompany(string $id, $relations = [], $relationsCount = [])
    {
        $company = $this->companyRepository->getById($id, $relations, $relationsCount);
        if(is_null($company))
            throw new ModelNotFoundException(__('api.not_found'));
        return $company;
    }

}
