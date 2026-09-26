<?php

namespace App\Modules\V1\Payments\Infrastructure\Providers;

use App\Modules\V1\Payments\Domain\Repositories\AdminPaymentRepositoryInterface;
use App\Modules\V1\Payments\Infrastructure\Persistence\Repositories\EloquentAdminPaymentRepository;
use Illuminate\Support\ServiceProvider;

class PaymentsModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AdminPaymentRepositoryInterface::class,
            EloquentAdminPaymentRepository::class,
        );
    }
}
