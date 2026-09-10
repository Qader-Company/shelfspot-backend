<?php

namespace App\Modules\V1\WorkersWallets\Infrastructure\Providers;

use App\Modules\V1\WorkersWallets\Console\Commands\BackfillTaskEarningsCommand;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WithdrawalRequestRepositoryInterface;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WorkerWalletRepositoryInterface;
use App\Modules\V1\WorkersWallets\Infrastructure\Persistence\Repositories\EloquentWithdrawalRequestRepository;
use App\Modules\V1\WorkersWallets\Infrastructure\Persistence\Repositories\EloquentWorkerWalletRepository;
use Illuminate\Support\ServiceProvider;

class WorkersWalletsModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WorkerWalletRepositoryInterface::class, EloquentWorkerWalletRepository::class);
        $this->app->bind(WithdrawalRequestRepositoryInterface::class, EloquentWithdrawalRequestRepository::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([BackfillTaskEarningsCommand::class]);
        }
    }
}
