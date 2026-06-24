<?php

namespace App\Console\Commands;

use App\Modules\V1\Tasks\Application\UseCases\AutoAcceptExpiredReviewTasksUseCase;
use Illuminate\Console\Command;

class AutoAcceptExpiredReviewTasksCommand extends Command
{
    protected $signature = 'tasks:auto-accept-expired-review {--limit= : Maximum number of tasks to process}';

    protected $description = 'Automatically accept completed tasks after their company review window expires.';

    public function handle(AutoAcceptExpiredReviewTasksUseCase $useCase): int
    {
        $limit = $this->option('limit');
        $accepted = $useCase->execute($limit !== null ? (int) $limit : null);

        $this->info("Auto-accepted {$accepted} expired review task(s).");

        return self::SUCCESS;
    }
}
