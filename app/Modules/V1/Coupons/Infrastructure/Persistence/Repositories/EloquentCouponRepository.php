<?php

namespace App\Modules\V1\Coupons\Infrastructure\Persistence\Repositories;

use App\Modules\V1\Coupons\Domain\Models\Coupon;
use App\Modules\V1\Coupons\Domain\Repositories\CouponRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EloquentCouponRepository implements CouponRepositoryInterface
{

    public function getAll(array $relations = [], array $relationsCount = [], array $filters = []): LengthAwarePaginator
    {
        return $this->query($relations, $relationsCount, $filters)
            ->paginate(request('per_page', 15));
    }

    public function getById(int $id, array $relations = [], array $relationsCount = []): ?Coupon
    {
        return $this->query($relations, $relationsCount)
            ->where('id', $id)
            ->first();
    }

    public function create(array $attributes): Coupon
    {
        return DB::transaction(function () use ($attributes) {
            $coupon = Coupon::create($attributes);

            return $coupon;
        });
    }

    public function update(Coupon $coupon, array $attributes): Coupon
    {
        return DB::transaction(function () use ($coupon, $attributes) {
            $coupon->update($attributes);

            return $coupon;
        });
    }

    public function delete(Coupon $coupon): void
    {
        $coupon->delete();
    }

    private function query(array $relations = [], array $relationsCount = [], array $filters = []): Builder
    {
        return Coupon::query()
            ->when($filters, fn (Builder $query) => $query->filter($filters))
            ->when($relations, fn (Builder $query) => $query->with($relations))
            ->when($relationsCount, fn (Builder $query) => $query->withCount($relationsCount));
    }
}
