<?php

namespace App\Modules\V1\WorkersWallets\Console\Commands;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\WorkersWallets\Application\UseCases\SettleAcceptedTaskUseCase;
use Illuminate\Console\Command;
use Throwable;

class BackfillTaskEarningsCommand extends Command
{
    protected $signature = 'wallets:backfill-task-earnings {--limit= : Maximum number of accepted tasks to process}';

    protected $description = 'Create missing worker earnings for accepted tasks';

    public function handle(SettleAcceptedTaskUseCase $settleAcceptedTask): int
    {
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;
        $processed = 0;
        $failed = 0;

        $tasks = Task::query()
            ->where('status', TaskStatusEnum::ACCEPTED->value)
            ->whereNull('settled_at')
            ->orderBy('id')
            ->lazyById();

        if ($limit !== null) {
            $tasks = $tasks->take($limit);
        }

        $tasks->each(function (Task $task) use ($settleAcceptedTask, &$processed, &$failed): void {
            try {
                $settleAcceptedTask->execute($task);
                $processed++;
            } catch (Throwable $exception) {
                $failed++;
                $this->error("Task {$task->id}: {$exception->getMessage()}");
            }
        });

        $this->info("Settled {$processed} task(s); {$failed} failed.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
