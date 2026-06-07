<?php

namespace App\Modules\V1\Coupons\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Shared\Support\Traits\Filterable;
use App\Modules\V1\Coupons\Domain\Models\WalletCoupon;
use App\Modules\V1\Coupons\Domain\Repositories\CouponRepositoryInterface;
use App\Modules\V1\Coupons\Presentation\Http\Requests\StoreWalletCouponRequest;
use App\Modules\V1\Coupons\Presentation\Http\Requests\UpdateWalletCouponRequest;
use App\Modules\V1\Coupons\Presentation\Http\Resources\WalletCouponResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class WalletCouponController extends Controller
{
    use Filterable;

    public function __construct(private readonly CouponRepositoryInterface $couponRepository)
    {
    }

    public function index(Request $request)
    {
        $filters = $this->acceptedFilters($request, ['search', 'active']);
        $coupons = $this->couponRepository->getAll(
            relations: ['assignedCompany', 'creator'],
            filters: $filters
        );

        return ApiResponse::success(
            WalletCouponResource::collection($coupons)->response()->getData(true)
        );
    }

    public function show(int $id)
    {
        return ApiResponse::success(
            new WalletCouponResource($this->getCoupon($id, ['assignedCompany', 'creator']))
        );
    }

    public function store(StoreWalletCouponRequest $request)
    {
        $coupon = $this->couponRepository->create(array_merge($request->validated(), [
            'created_by' => auth()->id(),
        ]))->load(['assignedCompany', 'creator']);

        return ApiResponse::created(
            new WalletCouponResource($coupon),
            __('company.wallet.coupons.created')
        );
    }

    public function update(UpdateWalletCouponRequest $request, int $id)
    {
        $coupon = $this->couponRepository->update(
            $this->getCoupon($id),
            $request->validated()
        )->load(['assignedCompany', 'creator']);

        return ApiResponse::updated(
            new WalletCouponResource($coupon),
            __('company.wallet.coupons.updated')
        );
    }

    public function destroy(int $id)
    {
        $this->couponRepository->delete($this->getCoupon($id));

        return ApiResponse::deleted(__('company.wallet.coupons.deleted'));
    }

    private function getCoupon(int $id, array $relations = [], array $relationsCount = []): WalletCoupon
    {
        $coupon = $this->couponRepository->getById($id, $relations, $relationsCount);
        if (is_null($coupon)) {
            throw new ModelNotFoundException(__('api.not_found'));
        }

        return $coupon;
    }
}
