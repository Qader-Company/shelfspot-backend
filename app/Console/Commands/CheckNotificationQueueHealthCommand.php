<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckNotificationQueueHealthCommand extends Command
{
    protected $signature = 'notifications:health {--max-pending= : Maximum pending jobs allowed per notification queue}';

    protected $description = 'Check notification queue backlog and Reverb availability.';

    public function handle(): int
    {
        $problems = array_merge($this->queueBacklogProblems(), $this->reverbProblems());

        if ($problems === []) {
            $this->info('Notification delivery health check passed.');

            return self::SUCCESS;
        }

        Log::critical('Notification delivery health check failed.', ['problems' => $problems]);
        $this->error(implode(PHP_EOL, $problems));

        return self::FAILURE;
    }

    private function queueBacklogProblems(): array
    {
        if (config('queue.default') !== 'database') {
            return [];
        }

        $connection = config('queue.connections.database');
        $maxPending = (int) ($this->option('max-pending') ?? config('notifications.health.max_pending_per_queue'));
        $queueNames = array_values(config('notifications.queues'));
        $counts = DB::connection($connection['connection'])
            ->table($connection['table'])
            ->whereIn('queue', $queueNames)
            ->selectRaw('queue, count(*) as pending_jobs')
            ->groupBy('queue')
            ->pluck('pending_jobs', 'queue');

        return collect($queueNames)
            ->filter(fn (string $queue) => (int) $counts->get($queue, 0) > $maxPending)
            ->map(fn (string $queue) => "Notification queue [{$queue}] has {$counts->get($queue)} pending job(s); limit is {$maxPending}.")
            ->values()
            ->all();
    }

    private function reverbProblems(): array
    {
        if (config('broadcasting.default') !== 'reverb') {
            return [];
        }

        $options = config('reverb.apps.apps.0.options');
        $host = $options['host'] ?? null;
        $port = (int) ($options['port'] ?? 0);

        if ($host === null || $port === 0) {
            return ['Reverb health check could not run because host or port is not configured.'];
        }

        $socket = @fsockopen($host, $port, $errorCode, $errorMessage, config('notifications.health.reverb_timeout_seconds'));

        if ($socket === false) {
            return ["Reverb is unavailable at {$host}:{$port} ({$errorMessage}, code {$errorCode})."];
        }

        fclose($socket);

        return [];
    }
}
