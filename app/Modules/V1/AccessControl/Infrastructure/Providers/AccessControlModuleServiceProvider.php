<?php

namespace App\Modules\V1\AccessControl\Infrastructure\Providers;

use App\Modules\V1\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use App\Modules\V1\AccessControl\Infrastructure\Persistence\Repositories\EloquentAccessControlRepository;
use Illuminate\Support\ServiceProvider;

class AccessControlModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccessControlRepositoryInterface::class, EloquentAccessControlRepository::class);
    }
}
