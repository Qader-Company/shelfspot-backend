<?php

namespace App\Modules\V1\CompaniesWallets\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\V1\CompaniesWallets\Application\UseCases\RechargeWalletUseCase;
use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\CompaniesWallets\Domain\Repositories\CompaniesWalletRepositoryInterface;
use App\Modules\V1\CompaniesWallets\Presentation\Http\Requests\CompanyWalletIndexRequest;
use App\Modules\V1\CompaniesWallets\Presentation\Http\Requests\RechargeWalletRequest;
use App\Modules\V1\CompaniesWallets\Presentation\Http\Resources\CompanyWalletResource;
use App\Modules\V1\Coupons\Application\UseCases\RedeemWalletCouponUseCase;
use App\Modules\V1\Coupons\Presentation\Http\Requests\RedeemWalletCouponRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CompanyWalletController extends Controller
{
    public function __construct(private readonly CompaniesWalletRepositoryInterface $walletRepository) {}

    public function index(CompanyWalletIndexRequest $request)
    {
        $transactions = $this->walletRepository->getAll(
            relations: ['performedBy'],
            filters: $request->filters()
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
        $transaction = $rechargeUseCase->execute(
            $request->validated(),
            $request->user()?->id
        )->load('performedBy');

        return ApiResponse::success([
            'balance' => (float) $transaction->balance_after,
            'transaction' => new CompanyWalletResource($transaction),
        ], __('company.wallet.transaction.success'));
    }

    public function redeemCoupon(RedeemWalletCouponRequest $request, RedeemWalletCouponUseCase $redeemWalletCouponUseCase, TenantContextInterface $tenantContext)
    {
        $transaction = $redeemWalletCouponUseCase->execute(
            $request->validated('code'),
            $tenantContext->getCompanyId(),
            $request->user()?->id,
        )->load('performedBy');

        return ApiResponse::success([
            'balance' => (float) $transaction->balance_after,
            'transaction' => new CompanyWalletResource($transaction),
        ], __('company.wallet.coupons.redeemed'));
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
