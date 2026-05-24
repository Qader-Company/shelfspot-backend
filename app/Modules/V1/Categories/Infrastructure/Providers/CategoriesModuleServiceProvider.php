<?php

namespace App\Modules\V1\Categories\Infrastructure\Providers;

use App\Modules\V1\Categories\Domain\Repositories\CategoryRepositoryInterface;
use App\Modules\V1\Categories\Infrastructure\Persistence\Repositories\EloquentCategoryRepository;
use Illuminate\Support\ServiceProvider;

class CategoriesModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
    }
}
