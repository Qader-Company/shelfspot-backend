<?php


namespace App\Modules\V1\Users\Infrastructure\Providers;

use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Users\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use App\Providers\AppServiceProvider;

class UserModuleServiceProvider extends AppServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );
    }
}
