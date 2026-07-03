<?php

namespace App\Console\Commands;

use App\Modules\V1\Tasks\Application\UseCases\ReleaseExpiredStartedTasksUseCase;
use Illuminate\Console\Command;

class ReleaseExpiredStartedTasksCommand extends Command
{
    protected $signature = 'tasks:release-expired-started {--limit=100 : Maximum number of tasks to process}';

    protected $description = 'Release accepted tasks whose start deadline expired back to pending.';

    public function handle(ReleaseExpiredStartedTasksUseCase $useCase): int
    {
        $limit = $this->option('limit');
        $released = $useCase->execute($limit !== null ? (int) $limit : null);

        $this->info("Released {$released} expired started task(s).");

        return self::SUCCESS;
    }
}
