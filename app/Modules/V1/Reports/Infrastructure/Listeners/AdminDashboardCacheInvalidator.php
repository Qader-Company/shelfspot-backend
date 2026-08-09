<?php

namespace App\Modules\V1\Reports\Infrastructure\Listeners;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\CompaniesWallets\Domain\Models\CompanyWalletTransaction;
use App\Modules\V1\Reports\Application\Caching\AdminDashboardCache;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Models\TaskWorkerAssignment;
use App\Modules\V1\Workers\Domain\Models\Worker;

final readonly class AdminDashboardCacheInvalidator
{
    public function __construct(private AdminDashboardCache $cache) {}

    public function register(): void
    {
        foreach ([
            Company::class,
            CompanyWalletTransaction::class,
            Task::class,
            TaskWorkerAssignment::class,
            Worker::class,
        ] as $model) {
            $model::saved(fn () => $this->invalidate());
            $model::deleted(fn () => $this->invalidate());
        }

        Company::restored(fn () => $this->invalidate());
        Worker::restored(fn () => $this->invalidate());
    }

    private function invalidate(): void
    {
        $this->cache->forgetAfterCommit();
    }
}
