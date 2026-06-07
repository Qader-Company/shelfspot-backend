<?php

namespace App\Modules\V1\WorkersWallets\Infrastructure\Providers;

use App\Modules\V1\WorkersWallets\Domain\Repositories\WorkersWalletRepositoryInterface;
use App\Modules\V1\WorkersWallets\Infrastructure\Persistence\Repositories\EloquentWorkersWalletRepository;
use Illuminate\Support\ServiceProvider;

class WorkersWalletsModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WorkersWalletRepositoryInterface::class, EloquentWorkersWalletRepository::class);
    }
}
