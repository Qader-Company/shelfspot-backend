<?php

namespace App\Console\Commands;

use App\Modules\V1\Tasks\Application\UseCases\FailExpiredTaskUseCase;
use Illuminate\Console\Command;

class FailExpiredTasksCommand extends Command
{
    protected $signature = 'tasks:fail-expired {--limit= : Maximum number of tasks to process}';

    protected $description = 'Fail expired pending tasks and release accepted tasks whose start deadline expired.';

    public function handle(FailExpiredTaskUseCase $failExpiredTaskUseCase): int
    {
        $limit = $this->option('limit');
        $failed = $failExpiredTaskUseCase->execute($limit !== null ? (int) $limit : null);

        $this->info("Processed {$failed} expired task(s).");

        return self::SUCCESS;
    }
}
