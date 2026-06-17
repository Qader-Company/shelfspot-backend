<?php

namespace App\Modules\V1\CompanyUsers\Infrastructure\Providers;

use App\Modules\V1\CompanyUsers\Domain\Repositories\CompanyUserRepositoryInterface;
use App\Modules\V1\CompanyUsers\Infrastructure\Persistence\Repositories\EloquentCompanyUserRepository;
use Illuminate\Support\ServiceProvider;

class CompanyUsersModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompanyUserRepositoryInterface::class, EloquentCompanyUserRepository::class);
    }
}
