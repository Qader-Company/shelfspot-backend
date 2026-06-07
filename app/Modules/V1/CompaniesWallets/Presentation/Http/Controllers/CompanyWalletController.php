<?php

namespace App\Modules\V1\CompaniesWallets\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\CompaniesWallets\Application\UseCases\RechargeWalletUseCase;
use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\CompaniesWallets\Domain\Repositories\CompaniesWalletRepositoryInterface;
use App\Modules\V1\CompaniesWallets\Presentation\Http\Requests\RechargeWalletRequest;
use App\Modules\V1\CompaniesWallets\Presentation\Http\Resources\CompanyWalletResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class CompanyWalletController extends Controller
{
    use Filterable;

    public function __construct(readonly private CompaniesWalletRepositoryInterface $walletRepository)
    {
    }

    public function index(Request $request)
    {
        $filter = $this->acceptedFilters($request, ['type']);
        $transactions = $this->walletRepository->getAll(
            relations: ['performedBy'],
            filters: $filter
        );

        return ApiResponse::success([
            'balance' => $this->walletRepository->currentBalance(),
            'transactions' => CompanyWalletResource::collection($transactions)->response()->getData(true),
        ]);
    }

    public function show(int $id)
    {
        $transaction = $this->getTransaction($id, ['performedBy']);

        return ApiResponse::success(new CompanyWalletResource($transaction));
    }

    public function recharge(RechargeWalletRequest $request, RechargeWalletUseCase $rechargeUseCase)
    {
        $transaction = $rechargeUseCase->execute($request->validated())->load('performedBy');

        return ApiResponse::success([
            'balance' => (float) $transaction->balance_after,
            'transaction' => new CompanyWalletResource($transaction),
        ], __('company.wallet.transaction.success'));
    }

    private function getTransaction(int $id, array $relations = [], array $relationsCount = []): CompanyWalletTransaction
    {
        $transaction = $this->walletRepository->getById($id, $relations, $relationsCount);
        if (is_null($transaction)) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return $transaction;
    }
}
