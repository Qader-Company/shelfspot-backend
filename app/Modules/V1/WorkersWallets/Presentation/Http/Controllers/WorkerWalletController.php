<?php

namespace App\Modules\V1\WorkersWallets\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\WorkersWallets\Application\UseCases\CreateWithdrawalRequestUseCase;
use App\Modules\V1\WorkersWallets\Application\UseCases\GetWorkerWalletOverviewUseCase;
use App\Modules\V1\WorkersWallets\Presentation\Http\Requests\StoreWithdrawalRequest;
use App\Modules\V1\WorkersWallets\Presentation\Http\Requests\WorkerWalletTransactionIndexRequest;
use App\Modules\V1\WorkersWallets\Presentation\Http\Resources\WithdrawalRequestResource;
use App\Modules\V1\WorkersWallets\Presentation\Http\Resources\WorkerWalletTransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WorkerWalletController extends Controller
{
    public function index(
        WorkerWalletTransactionIndexRequest $request,
        GetWorkerWalletOverviewUseCase $getWalletOverview,
    ): JsonResponse {
        $wallet = $getWalletOverview->execute(
            $this->worker($request),
            $request->filters(),
        );

        return ApiResponse::success([
            'statistics' => $wallet['statistics'],
            'transactions' => WorkerWalletTransactionResource::collection($wallet['transactions'])
                ->response()
                ->getData(true),
        ]);
    }

    public function storeWithdrawal(
        StoreWithdrawalRequest $request,
        CreateWithdrawalRequestUseCase $createWithdrawal,
    ): JsonResponse {
        $withdrawal = $createWithdrawal->execute(
            $this->worker($request),
            $request->validated(),
        );

        return ApiResponse::created(new WithdrawalRequestResource($withdrawal));
    }

    private function worker(Request $request): Worker
    {
        $worker = $request->user()?->worker;

        if (! $worker || ! $worker->is_active) {
            throw new AccessDeniedHttpException(__('api.forbidden'));
        }

        return $worker;
    }
}
