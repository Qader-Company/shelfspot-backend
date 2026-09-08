<?php

namespace App\Modules\V1\Stores\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\Shared\Presentation\Http\Controllers\ManagesTrash;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Stores\Domain\Models\Store;
use App\Modules\V1\Stores\Domain\Repositories\StoreRepositoryInterface;
use App\Modules\V1\Stores\Presentation\Http\Requests\StoreStoreRequest;
use App\Modules\V1\Stores\Presentation\Http\Requests\UpdateStoreRequest;
use App\Modules\V1\Stores\Presentation\Http\Resources\StoreResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class StoreController extends Controller
{
    use Filterable, ManagesTrash;

    public function __construct(private readonly StoreRepositoryInterface $storeRepository) {}

    public function index()
    {
        $stores = $this->storeRepository->getAll(
            $this->acceptedFilters(request(), ['search', 'active'])
        );

        return ApiResponse::success(StoreResource::collection($stores)->response()->getData(true));
    }

    public function options()
    {
        return ApiResponse::success(StoreResource::collection($this->storeRepository->options()));
    }

    public function show(string $id)
    {
        return ApiResponse::success(new StoreResource($this->getStore($id)));
    }

    public function store(StoreStoreRequest $request)
    {
        return ApiResponse::created(
            new StoreResource($this->storeRepository->create($request->validated()))
        );
    }

    public function update(UpdateStoreRequest $request, string $id)
    {
        return ApiResponse::updated(
            new StoreResource(
                $this->storeRepository->update($this->getStore($id), $request->validated())
            )
        );
    }

    public function destroy(string $id)
    {
        $this->storeRepository->delete($this->getStore($id));

        return ApiResponse::deleted();
    }

    protected function trashRepository(): TrashableRepositoryInterface
    {
        return $this->storeRepository;
    }

    protected function trashResourceCollection(LengthAwarePaginator $items): mixed
    {
        return StoreResource::collection($items)->response()->getData(true);
    }

    private function usesQueuedDelete(): bool
    {
        return false;
    }

    private function getStore(string $id): Store
    {
        $store = $this->storeRepository->getById((int) $id);

        if ($store === null) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return $store;
    }
}
