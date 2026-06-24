<?php

namespace App\Listeners;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workers\Domain\Models\Worker;

class StatusHistoryRecording
{
    /**
     * Create the event listener.
     */
    public function __construct(private readonly TaskStatusHistoryRecorder $statusHistoryRecorder)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TaskStatusUpdated $event): void
    {
        $this->statusHistoryRecorder->record(
            task: $event->task,
            fromStatus: $event->fromStatus,
            toStatus: $event->toStatus,
            actor: match (true) {
                $event->worker instanceof User => $event->worker,
                $event->worker instanceof Worker => $event->worker->user,
                default => null,
            },
            meta: $event->meta
        );
    }
}
