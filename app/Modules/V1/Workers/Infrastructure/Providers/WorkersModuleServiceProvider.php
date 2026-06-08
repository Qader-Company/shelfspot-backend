<?php

namespace App\Modules\V1\Workers\Infrastructure\Providers;

use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use App\Modules\V1\Workers\Infrastructure\Persistence\Repositories\EloquentWorkerRepository;
use Illuminate\Support\ServiceProvider;

class WorkersModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WorkerRepositoryInterface::class, EloquentWorkerRepository::class);
    }
}
