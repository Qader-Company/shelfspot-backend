<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PruneNotificationsCommand extends Command
{
    protected $signature = 'notifications:prune
        {--read-days= : Delete notifications read at least this many days ago}
        {--unread-days= : Delete unread notifications created at least this many days ago}
        {--delivery-days= : Delete notification delivery records created at least this many days ago}
        {--dry-run : Report what would be deleted without changing the database}';

    protected $description = 'Delete expired notifications and old notification delivery records.';

    public function handle(): int
    {
        $readDays = $this->retentionDays('read-days', 'notifications.retention.read_days');
        $unreadDays = $this->retentionDays('unread-days', 'notifications.retention.unread_days');
        $deliveryDays = $this->retentionDays('delivery-days', 'notifications.retention.delivery_days');

        if ($readDays === null || $unreadDays === null || $deliveryDays === null) {
            return self::FAILURE;
        }

        $readNotifications = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('read_at', '<=', now()->subDays($readDays));
        $unreadNotifications = DB::table('notifications')
            ->whereNull('read_at')
            ->where('created_at', '<=', now()->subDays($unreadDays));
        $deliveryRecords = DB::table('notification_deliveries')
            ->where('created_at', '<=', now()->subDays($deliveryDays));

        $counts = [
            'read notifications' => $this->prune($readNotifications),
            'unread notifications' => $this->prune($unreadNotifications),
            'delivery records' => $this->prune($deliveryRecords),
        ];

        $verb = $this->option('dry-run') ? 'Would delete' : 'Deleted';

        foreach ($counts as $label => $count) {
            $this->line("{$verb} {$count} {$label}.");
        }

        return self::SUCCESS;
    }

    private function retentionDays(string $option, string $configKey): ?int
    {
        $value = $this->option($option);
        $days = filter_var(
            $value === null ? config($configKey) : $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if ($days === false) {
            $this->error("The --{$option} value must be a positive integer.");

            return null;
        }

        return $days;
    }

    private function prune(Builder $query): int
    {
        return $this->option('dry-run')
            ? $query->count()
            : $query->delete();
    }
}
