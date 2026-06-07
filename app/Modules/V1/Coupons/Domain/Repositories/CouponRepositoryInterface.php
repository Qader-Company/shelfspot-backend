<?php

namespace App\Modules\V1\Coupons\Domain\Repositories;

use App\Modules\V1\Coupons\Domain\Models\Coupon;
use Illuminate\Pagination\LengthAwarePaginator;

interface CouponRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator;

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Coupon;

    public function create(array $attributes): Coupon;

    public function update(Coupon $coupon, array $attributes): Coupon;

    public function delete(Coupon $coupon): void;
}
