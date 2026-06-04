<?php

namespace App\Modules\Shared\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\Shared\Presentation\Http\Requests\BulkActionRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

trait ManagesTrash
{
    abstract protected function trashRepository(): TrashableRepositoryInterface;

    abstract protected function trashResourceCollection(LengthAwarePaginator $items): mixed;

    public function trash()
    {
        return ApiResponse::success(
            $this->trashResourceCollection($this->trashRepository()->getTrash())
        );
    }

    public function bulkDelete(BulkActionRequest $request)
    {
        $count = $this->trashRepository()->bulkDelete($request->ids());

        return ApiResponse::message(__('api.bulk_deleted', ['count' => $count]));
    }

    public function restore(string $id)
    {
        $this->ensureTrashActionSucceeded($this->trashRepository()->restore((int) $id));

        return ApiResponse::message(__('api.restored'));
    }

    public function bulkRestore(BulkActionRequest $request)
    {
        $count = $this->trashRepository()->bulkRestore($request->ids());

        return ApiResponse::message(__('api.bulk_restored', ['count' => $count]));
    }

    public function forceDelete(string $id)
    {
        $this->ensureTrashActionSucceeded($this->trashRepository()->forceDelete((int) $id));

        return ApiResponse::message(__('api.force_deleted'));
    }

    public function bulkForceDelete(BulkActionRequest $request)
    {
        $count = $this->trashRepository()->bulkForceDelete($request->ids());

        return ApiResponse::message(__('api.bulk_force_deleted', ['count' => $count]));
    }

    private function ensureTrashActionSucceeded(bool $succeeded): void
    {
        if (! $succeeded) {
            throw new ModelNotFoundException(__('api.not_found'));
        }
    }
}
