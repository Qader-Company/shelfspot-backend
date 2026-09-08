<?php

namespace App\Providers;

use App\Facades\ApiResponse;
use App\Facades\FacadesLogic\ApiResponseLogic;
use App\Modules\Shared\Domain\Contracts\TenantContextInterface;
use App\Modules\Shared\Infrastructure\Tenant\TenantContext;
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
                    ->prefix('api/'.($route['version'] ?? 'v1').'/'.($route['prefix'] ?? ''))
                    ->group(base_path('routes/'.($route['directory'] ?? 'V1/'.$key).'/'.($route['file'] ?? '')));
            }
        }
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
