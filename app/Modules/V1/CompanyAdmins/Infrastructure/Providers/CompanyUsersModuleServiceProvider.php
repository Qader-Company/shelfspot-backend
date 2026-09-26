<?php

namespace App\Modules\V1\CompanyAdmins\Infrastructure\Providers;

use App\Modules\V1\CompanyAdmins\Domain\Repositories\CompanyUserRepositoryInterface;
use App\Modules\V1\CompanyAdmins\Infrastructure\Persistence\Repositories\EloquentCompanyUserRepository;
use Illuminate\Support\ServiceProvider;

class CompanyUsersModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompanyUserRepositoryInterface::class, EloquentCompanyUserRepository::class);
    }
}
