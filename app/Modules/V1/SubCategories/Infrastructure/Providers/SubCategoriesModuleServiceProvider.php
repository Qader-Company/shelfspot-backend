<?php

namespace App\Modules\V1\SubCategories\Infrastructure\Providers;

use App\Modules\V1\SubCategories\Domain\Repositories\SubCategoryRepositoryInterface;
use App\Modules\V1\SubCategories\Infrastructure\Persistence\Repositories\EloquentSubCategoryRepository;
use Illuminate\Support\ServiceProvider;

class SubCategoriesModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SubCategoryRepositoryInterface::class, EloquentSubCategoryRepository::class);
    }
}
