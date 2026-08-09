<?php

namespace App\Modules\V1\Reports\Infrastructure\Listeners;

use App\Modules\V1\Reports\Application\Caching\CompanyDashboardCache;
use App\Modules\V1\Tasks\Domain\Models\Task;

final readonly class CompanyDashboardCacheInvalidator
{
    public function __construct(private CompanyDashboardCache $cache) {}

    public function register(): void
    {
        Task::saved(fn (Task $task) => $this->invalidate($task));
        Task::deleted(fn (Task $task) => $this->invalidate($task));
    }

    private function invalidate(Task $task): void
    {
        $companyIds = array_values(array_filter([
            $task->company_id,
            $task->getOriginal('company_id'),
        ]));

        $this->cache->forgetAfterCommit(...$companyIds);
    }
}
