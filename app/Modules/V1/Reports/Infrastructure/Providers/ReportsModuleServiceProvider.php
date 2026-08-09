<?php

namespace App\Modules\V1\Reports\Infrastructure\Providers;

use App\Modules\V1\Reports\Infrastructure\Listeners\AdminDashboardCacheInvalidator;
use App\Modules\V1\Reports\Infrastructure\Listeners\CompanyDashboardCacheInvalidator;
use Illuminate\Support\ServiceProvider;

class ReportsModuleServiceProvider extends ServiceProvider
{
    public function boot(
        AdminDashboardCacheInvalidator $adminDashboardCacheInvalidator,
        CompanyDashboardCacheInvalidator $companyDashboardCacheInvalidator,
    ): void {
        $adminDashboardCacheInvalidator->register();
        $companyDashboardCacheInvalidator->register();
    }
}
