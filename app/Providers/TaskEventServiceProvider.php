<?php

namespace App\Providers;

use App\Events\TaskStatusUpdated;
use App\Listeners\CaptureTaskStatusNotificationSnapshot;
use App\Listeners\SendTaskStatusNotification;
use App\Listeners\StatusHistoryRecording;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class TaskEventServiceProvider extends ServiceProvider
{
    public function boot(Dispatcher $events): void
    {
        $events->listen(TaskStatusUpdated::class, StatusHistoryRecording::class);
        $events->listen(TaskStatusUpdated::class, CaptureTaskStatusNotificationSnapshot::class);
        $events->listen(TaskStatusUpdated::class, SendTaskStatusNotification::class);
    }
}
