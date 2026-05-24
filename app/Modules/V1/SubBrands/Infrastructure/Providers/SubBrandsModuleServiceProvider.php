<?php
namespace App\Modules\V1\SubBrands\Infrastructure\Providers;

use App\Modules\V1\SubBrands\Domain\Repositories\SubBrandRepositoryInterface;
use App\Modules\V1\SubBrands\Infrastructure\Persistence\Repositories\EloquentSubBrandRepository;
use Illuminate\Support\ServiceProvider;

class SubBrandsModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SubBrandRepositoryInterface::class, EloquentSubBrandRepository::class);
    }
}
