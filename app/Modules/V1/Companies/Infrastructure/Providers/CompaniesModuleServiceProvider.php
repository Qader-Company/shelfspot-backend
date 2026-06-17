<?php


namespace App\Modules\V1\Companies\Infrastructure\Providers;

use App\Modules\V1\Companies\Domain\Repositories\CompanyRepositoryInterface;
use App\Modules\V1\Companies\Infrastructure\Persistence\Repositories\EloquentCompanyRepository;
use App\Providers\AppServiceProvider;

class CompaniesModuleServiceProvider extends AppServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CompanyRepositoryInterface::class,
            EloquentCompanyRepository::class
        );
    }
}
