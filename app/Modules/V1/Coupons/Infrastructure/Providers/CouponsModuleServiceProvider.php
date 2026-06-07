<?php

namespace App\Modules\V1\Coupons\Infrastructure\Providers;

use App\Modules\V1\Coupons\Domain\Repositories\CouponRepositoryInterface;
use App\Modules\V1\Coupons\Infrastructure\Persistence\Repositories\EloquentCouponRepository;
use Illuminate\Support\ServiceProvider;

class CouponsModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CouponRepositoryInterface::class, EloquentCouponRepository::class);
    }
}
