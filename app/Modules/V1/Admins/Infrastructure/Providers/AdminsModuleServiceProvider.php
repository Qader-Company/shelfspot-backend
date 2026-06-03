<?php

namespace App\Modules\V1\Admins\Infrastructure\Providers;

use App\Modules\V1\Admins\Domain\Repositories\{AdminRepositoryInterface};
use App\Modules\V1\Admins\Infrastructure\Persistence\Repositories\{EloquentAdminRepository};
use Illuminate\Support\ServiceProvider;

class AdminsModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AdminRepositoryInterface::class,
            EloquentAdminRepository::class
        );
    }
}
