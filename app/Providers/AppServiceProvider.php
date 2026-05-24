<?php

namespace App\Providers;

use App\Facades\ApiResponse;
use App\Console\Commands\MakeModuleCommand;
use App\Facades\FacadesLogic\ApiResponseLogic;
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

        $this->commands([
            MakeModuleCommand::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        foreach (config('routing.public') as $route) {
            Route::middleware(['api', 'locale'])
                ->prefix('api/v1' . (!empty($route['prefix']) ? '/' . $route['prefix'] : ''))
                ->group(base_path('routes/V1/' . $route['file']));
        }

        foreach (config('routing.portals') as $key => $portal) {
            foreach ($portal as $route){
                $middlewares = array_merge(
                    ['api', 'locale'],
                    $route['middlewares']
                );
                Route::middleware($middlewares)
                    ->prefix('api/v1/' . $key .'/'. $route['prefix'])
                    ->group(base_path('routes/V1/' . $key .'/' .$route['file']));
            }
        }
    }

}
