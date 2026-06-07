<?php

namespace App\Modules\V1\CompaniesWallets\Infrastructure\Providers;

use App\Modules\V1\CompaniesWallets\Domain\Repositories\CompaniesWalletRepositoryInterface;
use App\Modules\V1\CompaniesWallets\Infrastructure\Persistence\Repositories\EloquentCompaniesWalletRepository;
use Illuminate\Support\ServiceProvider;

class CompaniesWalletsModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompaniesWalletRepositoryInterface::class, EloquentCompaniesWalletRepository::class);
    }
}
