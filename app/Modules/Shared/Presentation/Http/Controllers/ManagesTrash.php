<?php

namespace App\Modules\Shared\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Modules\Shared\Domain\Repositories\TrashableRepositoryInterface;
use App\Modules\Shared\Presentation\Http\Requests\BulkActionRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

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

        return ApiResponse::message(
            $this->usesQueuedDelete()
                ? __('api.bulk_delete_queued', ['count' => $count])
                : __('api.bulk_deleted', ['count' => $count]),
            $this->usesQueuedDelete() ? Response::HTTP_ACCEPTED : Response::HTTP_OK
        );
    }

    public function restore(string $id)
    {
        $this->ensureTrashActionSucceeded($this->trashRepository()->restore((int) $id));

        return ApiResponse::message(
            $this->usesQueuedRestore() ? __('api.restore_queued') : __('api.restored'),
            $this->usesQueuedRestore() ? Response::HTTP_ACCEPTED : Response::HTTP_OK
        );
    }

    public function bulkRestore(BulkActionRequest $request)
    {
        $count = $this->trashRepository()->bulkRestore($request->ids());

        return ApiResponse::message(
            $this->usesQueuedRestore()
                ? __('api.bulk_restore_queued', ['count' => $count])
                : __('api.bulk_restored', ['count' => $count]),
            $this->usesQueuedRestore() ? Response::HTTP_ACCEPTED : Response::HTTP_OK
        );
    }

    public function forceDelete(string $id)
    {
        $this->ensureTrashActionSucceeded($this->trashRepository()->forceDelete((int) $id));

        return ApiResponse::message(
            $this->usesQueuedForceDelete() ? __('api.force_delete_queued') : __('api.force_deleted'),
            $this->usesQueuedForceDelete() ? Response::HTTP_ACCEPTED : Response::HTTP_OK
        );
    }

    public function bulkForceDelete(BulkActionRequest $request)
    {
        $count = $this->trashRepository()->bulkForceDelete($request->ids());

        return ApiResponse::message(
            $this->usesQueuedForceDelete()
                ? __('api.bulk_force_delete_queued', ['count' => $count])
                : __('api.bulk_force_deleted', ['count' => $count]),
            $this->usesQueuedForceDelete() ? Response::HTTP_ACCEPTED : Response::HTTP_OK
        );
    }

    private function usesQueuedForceDelete(): bool
    {
        return method_exists($this->trashRepository(), 'usesQueuedForceDelete')
            && $this->trashRepository()->usesQueuedForceDelete();
    }

    private function ensureTrashActionSucceeded(bool $succeeded): void
    {
        if (! $succeeded) {
            throw new ModelNotFoundException(__('api.not_found'));
        }
    }
}
