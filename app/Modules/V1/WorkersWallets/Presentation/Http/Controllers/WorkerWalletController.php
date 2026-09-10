<?php

namespace App\Modules\V1\WorkersWallets\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\WorkersWallets\Application\UseCases\CreateWithdrawalRequestUseCase;
use App\Modules\V1\WorkersWallets\Application\UseCases\GetWorkerWalletOverviewUseCase;
use App\Modules\V1\WorkersWallets\Domain\Models\WithdrawalRequest;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WithdrawalRequestRepositoryInterface;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WorkerWalletRepositoryInterface;
use App\Modules\V1\WorkersWallets\Presentation\Http\Requests\StoreWithdrawalRequest;
use App\Modules\V1\WorkersWallets\Presentation\Http\Requests\WorkerWalletTransactionIndexRequest;
use App\Modules\V1\WorkersWallets\Presentation\Http\Requests\WorkerWithdrawalIndexRequest;
use App\Modules\V1\WorkersWallets\Presentation\Http\Resources\WithdrawalRequestResource;
use App\Modules\V1\WorkersWallets\Presentation\Http\Resources\WorkerWalletTransactionResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WorkerWalletController extends Controller
{
    public function __construct(
        private readonly WorkerWalletRepositoryInterface $walletRepository,
        private readonly WithdrawalRequestRepositoryInterface $withdrawalRepository,
    ) {}

    public function show(Request $request, GetWorkerWalletOverviewUseCase $getWalletOverview): JsonResponse
    {
        $overview = $getWalletOverview->execute($this->worker($request));
        $latestTransactions = $overview['latest_transactions'];
        unset($overview['latest_transactions']);

        return ApiResponse::success([
            ...$overview,
            'latest_transactions' => WorkerWalletTransactionResource::collection($latestTransactions),
        ]);
    }

    public function transactions(WorkerWalletTransactionIndexRequest $request): JsonResponse
    {
        $transactions = $this->walletRepository->transactions(
            $this->worker($request)->id,
            $request->filters(),
        );

        return ApiResponse::success(
            WorkerWalletTransactionResource::collection($transactions)
                ->response()
                ->getData(true)
        );
    }

    public function withdrawals(WorkerWithdrawalIndexRequest $request): JsonResponse
    {
        $withdrawals = $this->withdrawalRepository->forWorker(
            $this->worker($request)->id,
            $request->filters(),
        );

        return ApiResponse::success(
            WithdrawalRequestResource::collection($withdrawals)
                ->response()
                ->getData(true)
        );
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

    public function showWithdrawal(int $withdrawal, Request $request): JsonResponse
    {
        return ApiResponse::success(new WithdrawalRequestResource(
            $this->withdrawal($this->worker($request), $withdrawal)
        ));
    }

    private function withdrawal(Worker $worker, int $withdrawalId): WithdrawalRequest
    {
        $withdrawal = $this->withdrawalRepository->findForWorker($worker->id, $withdrawalId);

        if (! $withdrawal) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return $withdrawal;
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
