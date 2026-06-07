<?php

namespace App\Modules\V1\Coupons\Domain\Repositories;

use App\Modules\V1\Coupons\Domain\Models\WalletCoupon;
use Illuminate\Pagination\LengthAwarePaginator;

interface CouponRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator;

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?WalletCoupon;

    public function findActiveByCode(string $code, array $relations = []): ?WalletCoupon;

    public function hasCompanyRedeemed(WalletCoupon $coupon, int $companyId): bool;

    public function incrementRedemptions(WalletCoupon $coupon): WalletCoupon;

    public function create(array $attributes): WalletCoupon;

    public function update(WalletCoupon $coupon, array $attributes): WalletCoupon;

    public function delete(WalletCoupon $coupon): void;
}
