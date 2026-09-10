<?php

namespace App\Modules\V1\WorkersWallets\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\WorkersWallets\Application\UseCases\ApproveWithdrawalRequestUseCase;
use App\Modules\V1\WorkersWallets\Application\UseCases\RejectWithdrawalRequestUseCase;
use App\Modules\V1\WorkersWallets\Domain\Models\WithdrawalRequest;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WithdrawalRequestRepositoryInterface;
use App\Modules\V1\WorkersWallets\Presentation\Http\Requests\AdminWithdrawalIndexRequest;
use App\Modules\V1\WorkersWallets\Presentation\Http\Requests\RejectWithdrawalRequest;
use App\Modules\V1\WorkersWallets\Presentation\Http\Resources\WithdrawalRequestResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminWithdrawalController extends Controller
{
    public function __construct(
        private readonly WithdrawalRequestRepositoryInterface $withdrawalRepository,
    ) {}

    public function index(AdminWithdrawalIndexRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $withdrawals = $this->withdrawalRepository->forAdmin($filters);

        return ApiResponse::success([
            'summary' => $this->withdrawalRepository->adminSummary($filters),
            'withdrawals' => WithdrawalRequestResource::collection($withdrawals)
                ->response()
                ->getData(true),
        ]);
    }

    public function show(int $withdrawal): JsonResponse
    {
        return ApiResponse::success(new WithdrawalRequestResource(
            $this->withdrawal($withdrawal)
        ));
    }

    public function approve(
        int $withdrawal,
        Request $request,
        ApproveWithdrawalRequestUseCase $approveWithdrawal,
    ): JsonResponse {
        $approved = $approveWithdrawal->execute(
            $this->withdrawal($withdrawal),
            $request->user(),
        );

        return ApiResponse::updated(new WithdrawalRequestResource($approved));
    }

    public function reject(
        int $withdrawal,
        RejectWithdrawalRequest $request,
        RejectWithdrawalRequestUseCase $rejectWithdrawal,
    ): JsonResponse {
        $rejected = $rejectWithdrawal->execute(
            $this->withdrawal($withdrawal),
            $request->user(),
            $request->validated('reason'),
        );

        return ApiResponse::updated(new WithdrawalRequestResource($rejected));
    }

    private function withdrawal(int $withdrawalId): WithdrawalRequest
    {
        $withdrawal = $this->withdrawalRepository->findForAdmin($withdrawalId);

        if (! $withdrawal) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return $withdrawal;
    }
}
