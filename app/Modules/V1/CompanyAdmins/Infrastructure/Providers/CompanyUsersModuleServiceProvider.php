<?php

namespace App\Modules\V1\CompanyAdmins\Infrastructure\Providers;

use App\Modules\V1\CompanyAdmins\Domain\Repositories\CompanyAdminRepositoryInterface;
use App\Modules\V1\CompanyAdmins\Infrastructure\Persistence\Repositories\EloquentCompanyUserRepository;
use Illuminate\Support\ServiceProvider;

class CompanyUsersModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompanyAdminRepositoryInterface::class, EloquentCompanyUserRepository::class);
    }
}
