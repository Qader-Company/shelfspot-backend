<?php

namespace App\Console\Commands;

use App\Modules\V1\Tasks\Application\UseCases\FailExpiredReopenedTasksUseCase;
use Illuminate\Console\Command;

class FailExpiredReopenedTasksCommand extends Command
{
    protected $signature = 'tasks:fail-expired-reopened {--limit=100 : Maximum number of tasks to process}';

    protected $description = 'Fail reopened tasks whose rework deadline has expired.';

    public function handle(FailExpiredReopenedTasksUseCase $useCase): int
    {
        $limit = $this->option('limit');
        $failed = $useCase->execute($limit !== null ? (int) $limit : null);

        $this->info("Processed {$failed} expired reopened task(s).");

        return self::SUCCESS;
    }
}
