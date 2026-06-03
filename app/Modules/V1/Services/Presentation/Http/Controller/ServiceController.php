<?php

namespace App\Modules\V1\Services\Presentation\Http\Controller;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Services\Domain\Repositories\ServiceRepositoryInterface;
use App\Modules\V1\Services\Presentation\Http\Requests\UpdateServiceRequest;
use App\Modules\V1\Services\Presentation\Http\Resources\ServiceResource;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    use Filterable;

    public function __construct(private readonly ServiceRepositoryInterface $serviceRepository)
    {
    }

    public function index(Request $request)
    {
        $filters = $this->acceptedFilters($request, ['active']);
        $this->ifUserNotAdmin($filters);
        $services = $this->serviceRepository->getAll(
            relations: ['translations'],
            filters: $filters,
        );
        return ApiResponse::success(ServiceResource::collection($services));
    }

    public function show(string $key)
    {
        $service = $this->getService($key, ['translations']);
        return ApiResponse::success(ServiceResource::withTranslations($service));
    }

    public function update(UpdateServiceRequest $request, int $id)
    {
        $service = $this->getService($id);
        $this->serviceRepository->update($service, $request->validated());
        return ApiResponse::message(__('api.updated'));
    }

    private function getService(string $id, $relations = [], $relationsCount = [])
    {
        $service = $this->serviceRepository->getById($id, $relations, $relationsCount);
        if(is_null($service)) throw new ModelNotFoundException(__('api.not_found'));
        return $service;
    }
    private function ifUserNotAdmin(&$filters)
    {
        $filters['active'] = (Auth::user()->type !== PortalTypeEnum::ADMIN) ? true : $filters['active'];
    }
}
