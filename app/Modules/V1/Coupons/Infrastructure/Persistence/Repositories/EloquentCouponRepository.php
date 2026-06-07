<?php

namespace App\Modules\V1\Coupons\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Coupons\Domain\Models\WalletCoupon;
use App\Modules\V1\Coupons\Domain\Repositories\CouponRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentCouponRepository implements CouponRepositoryInterface
{
    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->query($relations, $relationsCount, $filters)
            ->latest()
            ->paginate(request('per_page', 15));
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?WalletCoupon
    {
        return $this->query($relations, $relationsCount)
            ->where('id', $id)
            ->first();
    }

    public function findActiveByCode(string $code, array $relations = []): ?WalletCoupon
    {
        return $this->query($relations)
            ->where('code', strtoupper($code))
            ->where('is_active', true)
            ->first();
    }

    public function hasCompanyRedeemed(WalletCoupon $coupon, int $companyId): bool
    {
        return $coupon->redemptions()
            ->where('company_id', $companyId)
            ->exists();
    }

    public function incrementRedemptions(WalletCoupon $coupon): WalletCoupon
    {
        $coupon->increment('redemptions_count');

        return $coupon->refresh();
    }

    public function create(array $attributes): WalletCoupon
    {
        return DB::transaction(fn () => WalletCoupon::create($this->normalizeAttributes($attributes)));
    }

    public function update(WalletCoupon $coupon, array $attributes): WalletCoupon
    {
        return DB::transaction(function () use ($coupon, $attributes) {
            $coupon->update($this->normalizeAttributes($attributes));

            return $coupon->refresh();
        });
    }

    public function delete(WalletCoupon $coupon): void
    {
        $coupon->delete();
    }

    private function query(array $relations = [], array $relationsCount = [], array $filters = []): Builder
    {
        return WalletCoupon::query()
            ->when($filters, fn (Builder $query) => $query->filter($filters))
            ->when($relations, fn (Builder $query) => $query->with($relations))
            ->when($relationsCount, fn (Builder $query) => $query->withCount($relationsCount));
    }

    private function normalizeAttributes(array $attributes): array
    {
        if (isset($attributes['code'])) {
            $attributes['code'] = strtoupper($attributes['code']);
        }

        return $attributes;
    }
}
