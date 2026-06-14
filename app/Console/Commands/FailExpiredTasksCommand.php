<?php

namespace App\Console\Commands;

use App\Modules\V1\Tasks\Application\UseCases\FailExpiredTaskUseCase;
use Illuminate\Console\Command;

class FailExpiredTasksCommand extends Command
{
    protected $signature = 'tasks:fail-expired {--limit= : Maximum number of tasks to process}';

    protected $description = 'Mark pending or accepted tasks as failed when their execution windows expire.';

    public function handle(FailExpiredTaskUseCase $failExpiredTaskUseCase): int
    {
        $limit = $this->option('limit');
        $failed = $failExpiredTaskUseCase->execute($limit !== null ? (int) $limit : null);

        $this->info("Failed {$failed} expired task(s).");

        return self::SUCCESS;
    }
}
