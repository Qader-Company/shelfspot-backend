<?php

namespace App\Providers;

use App\Facades\ApiResponse;
use App\Facades\FacadesLogic\ApiResponseLogic;
use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\Shared\Infrastructure\Tenant\TenantContext;
use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\Reports\Application\Services\AdminDashboardCache;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskWorkerAssignment;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        foreach (config('modules.providers', []) as $provider) {
            $this->app->register($provider);
        }
        $this->app->bind(
            ApiResponse::class,
            ApiResponseLogic::class
        );

        $this->app->singleton(TenantContextInterface::class, TenantContext::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading();

        $this->registerRoutes();
        $this->registerAdminDashboardCacheInvalidation();
        $this->registerNotificationQueueFailureLogging();
    }

    private function registerRoutes(): void
    {
        foreach (config('modules.routes') as $key => $portal) {
            foreach ($portal as $route) {
                $middlewares = array_merge(
                    ['api', 'locale', 'api.key'],
                    $route['middlewares'] ?? []
                );
                Route::middleware($middlewares)
                    ->prefix('api/v1/'.($route['prefix'] ?? ''))
                    ->group(base_path('routes/V1/'.$key.'/'.($route['file'] ?? '')));
            }
        }
    }

    private function registerAdminDashboardCacheInvalidation(): void
    {
        foreach ([
            Company::class,
            CompanyWalletTransaction::class,
            Task::class,
            TaskWorkerAssignment::class,
            Worker::class,
        ] as $model) {
            $model::saved(fn () => AdminDashboardCache::forget());
            $model::deleted(fn () => AdminDashboardCache::forget());
        }

        Company::restored(fn () => AdminDashboardCache::forget());
        Worker::restored(fn () => AdminDashboardCache::forget());
    }

    private function registerNotificationQueueFailureLogging(): void
    {
        Queue::failing(function (JobFailed $event): void {
            if (! in_array($event->job->getQueue(), array_values(config('notifications.queues')), true)) {
                return;
            }

            Log::critical('Notification queue job failed.', [
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'job' => $event->job->resolveName(),
                'exception' => $event->exception::class,
                'message' => $event->exception->getMessage(),
            ]);
        });
    }
}
