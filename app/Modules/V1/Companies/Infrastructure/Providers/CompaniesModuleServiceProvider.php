<?php

namespace App\Modules\V1\Companies\Infrastructure\Providers;

use App\Modules\V1\Companies\Domain\Repositories\CompanyRepositoryInterface;
use App\Modules\V1\Companies\Infrastructure\Persistence\Repositories\EloquentCompanyRepository;
use Illuminate\Support\ServiceProvider;

class CompaniesModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CompanyRepositoryInterface::class,
            EloquentCompanyRepository::class
        );
    }
}
