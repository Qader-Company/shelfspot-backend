<?php

namespace App\Console\Commands;

use App\Modules\V1\Tasks\Application\UseCases\MarkOverdueInProgressTasksUseCase;
use Illuminate\Console\Command;

class MarkOverdueInProgressTasksCommand extends Command
{
    protected $signature = 'tasks:mark-overdue-in-progress {--limit=100 : Maximum number of tasks to process}';

    protected $description = 'Mark in-progress tasks whose estimated duration has been exceeded.';

    public function handle(MarkOverdueInProgressTasksUseCase $useCase): int
    {
        $limit = $this->option('limit');
        $marked = $useCase->execute($limit !== null ? (int) $limit : null);

        $this->info("Marked {$marked} overdue in-progress task(s).");

        return self::SUCCESS;
    }
}
