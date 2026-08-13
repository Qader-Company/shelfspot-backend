<?php

namespace App\Modules\V1\Tasks\Application\Services;

use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\AccessControl\Domain\ValueObjects\CompanyPermissionEnum;
use App\Modules\V1\Admins\Domain\Models\ShelfSpotAdmin;
use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;
use App\Modules\V1\Tasks\Application\Data\TaskStatusNotificationSnapshot;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workers\Application\Services\GeoDistanceCalculator;
use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\Workers\Domain\Repositories\WorkerRepositoryInterface;
use App\Notifications\RealtimeNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TaskNotificationDispatcher
{
    public const NEARBY_WORKER_NOTIFICATION_RADIUS_KM = 5.0;

    public const MAX_NEARBY_WORKER_NOTIFICATION_RECIPIENTS = 100;

    public const RECIPIENT_CHUNK_SIZE = 25;

    public function __construct(
        private readonly GeoDistanceCalculator $geoDistanceCalculator,
        private readonly WorkerRepositoryInterface $workerRepository,
    ) {}

    public function statusChanged(Task $task, TaskStatusEnum $fromStatus, TaskStatusEnum $toStatus, mixed $actor, array $meta = []): void
    {
        $snapshot = $this->capture(
            task: $task,
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            actor: $actor,
            statusHistoryId: $meta['status_history_id'] ?? null,
            meta: $meta,
            occurredAt: now()->toIso8601String(),
        );

        if ($snapshot !== null) {
            $this->dispatch($snapshot);
        }
    }

    public function capture(
        Task $task,
        TaskStatusEnum $fromStatus,
        TaskStatusEnum $toStatus,
        mixed $actor,
        ?int $statusHistoryId,
        array $meta,
        string $occurredAt,
    ): ?TaskStatusNotificationSnapshot {
        $event = $this->statusEvent($fromStatus, $toStatus, $meta);

        if ($event === null) {
            return null;
        }

        $actorId = $this->actorId($actor);
        $recipientIds = $this->statusRecipients($task, $event, $meta)
            ->filter(fn (User $recipient) => $actorId === null || (int) $recipient->id !== (int) $actorId)
            ->unique('id')
            ->pluck('id')
            ->map(fn (int $recipientId) => (int) $recipientId)
            ->values()
            ->all();

        if ($statusHistoryId === null) {
            return null;
        }

        return new TaskStatusNotificationSnapshot(
            taskId: $task->id,
            companyId: $task->company_id,
            event: $event,
            priority: $this->priorityFor($event),
            fromStatus: $fromStatus->value,
            toStatus: $toStatus->value,
            actorId: $actorId,
            recipientIds: $recipientIds,
            statusHistoryId: $statusHistoryId,
            meta: array_merge($meta, [
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
            ]),
            occurredAt: $occurredAt,
        );
    }

    public function dispatch(TaskStatusNotificationSnapshot $snapshot): void
    {
        $recipientsById = User::query()
            ->whereKey($snapshot->recipientIds)
            ->get()
            ->keyBy('id');

        $payload = [
            'event' => $snapshot->event,
            'category' => 'task',
            'priority' => $snapshot->priority,
            ...$this->contentFor($snapshot->event, $snapshot->taskId),
            'task_id' => $snapshot->taskId,
            'company_id' => $snapshot->companyId,
            'status' => $snapshot->toStatus,
            'actor_id' => $snapshot->actorId,
            'action' => ['resource' => 'task', 'id' => $snapshot->taskId],
            'meta' => array_merge($snapshot->meta, ['status_history_id' => $snapshot->statusHistoryId]),
            'occurred_at' => $snapshot->occurredAt,
        ];

        collect($snapshot->recipientIds)
            ->map(fn (int $recipientId) => $recipientsById->get($recipientId))
            ->filter()
            ->chunk(self::RECIPIENT_CHUNK_SIZE)
            ->each(fn (Collection $recipientsChunk) => $recipientsChunk
                ->each(fn (User $recipient) => $recipient->notify(new RealtimeNotification(
                    payload: $payload,
                    notificationKey: $this->notificationKey($snapshot->statusHistoryId, $snapshot->event, $recipient),
                ))));
    }

    private function statusEvent(TaskStatusEnum $fromStatus, TaskStatusEnum $toStatus, array $meta): ?string
    {
        return match ($toStatus) {
            TaskStatusEnum::STARTED => isset($meta['reassigned_worker_id']) ? 'task.reassigned' : null,
            TaskStatusEnum::COMPLETED => 'task.completed',
            TaskStatusEnum::ACCEPTED => null,
            TaskStatusEnum::REJECTED => 'task.rejected',
            TaskStatusEnum::REOPENED => 'task.reopened',
            TaskStatusEnum::WORKER_CANCELLED => 'task.worker_cancelled',
            TaskStatusEnum::COMPANY_CANCELLED => null,
            TaskStatusEnum::FAILED => 'task.failed',
            TaskStatusEnum::PENDING => $fromStatus === TaskStatusEnum::DRAFT ? 'task.published' : null,
            default => null,
        };
    }

    private function statusRecipients(Task $task, string $event, array $meta): Collection
    {
        return match ($event) {
            'task.published' => $this->nearbyWorkerUsers($task),
            'task.completed', 'task.failed' => $this->companyUsers($task->company_id),
            'task.rejected', 'task.worker_cancelled' => $this->taskAdmins(),
            'task.reopened' => $this->companyUsers($task->company_id)
                ->merge($this->workerUser($task->assigned_worker_id)),
            'task.reassigned' => $this->workerUser($task->assigned_worker_id),
            default => collect(),
        };
    }

    private function actorId(mixed $actor): ?int
    {
        return match (true) {
            $actor instanceof User => $actor->id,
            $actor instanceof Worker => $actor->user_id,
            default => null,
        };
    }

    private function companyUsers(int $companyId): Collection
    {
        return CompanyUser::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereHas('user.roles', function (Builder $query) use ($companyId) {
                $query
                    ->where('portal', PermissionCatalog::COMPANY_PORTAL)
                    ->where('company_id', $companyId)
                    ->whereHas('permissions', fn (Builder $query) => $query->where(
                        'name',
                        CompanyPermissionEnum::VIEW_TASK->value,
                    ));
            })
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();
    }

    private function workerUser(?int $workerId): Collection
    {
        if ($workerId === null) {
            return collect();
        }

        $worker = Worker::query()->with('user')->find($workerId);

        return $worker?->is_active && $worker->user ? collect([$worker->user]) : collect();
    }

    private function nearbyWorkerUsers(Task $task): Collection
    {
        $radius = self::NEARBY_WORKER_NOTIFICATION_RADIUS_KM;
        $latitude = (float) $task->latitude;
        $longitude = (float) $task->longitude;
        $boundingBox = $this->geoDistanceCalculator->boundingBox($latitude, $longitude, $radius);

        $workers = $this->workerRepository->availableNearTask(
            latitude: $latitude,
            longitude: $longitude,
            radiusKilometers: $radius,
            boundingBox: $boundingBox,
            limit: self::MAX_NEARBY_WORKER_NOTIFICATION_RECIPIENTS,
        );

        return $workers->pluck('user')->filter();
    }

    private function taskAdmins(): Collection
    {
        return ShelfSpotAdmin::query()
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter(fn (User $user) => $user->can(AdminPermissionEnum::REASSIGN_TASK->value));
    }

    private function priorityFor(string $event): string
    {
        return in_array($event, [
            'task.completed', 'task.rejected', 'task.reopened', 'task.reassigned', 'task.worker_cancelled', 'task.failed',
        ], true) ? 'high' : 'normal';
    }

    /**
     * @return array{title: string, description: string}
     */
    private function contentFor(string $event, int $taskId): array
    {
        $translationKey = 'notifications.'.str_replace('.', '_', $event);

        return [
            'title' => __($translationKey.'.title'),
            'description' => __($translationKey.'.description', ['task' => $taskId]),
        ];
    }

    private function notificationKey(?int $statusHistoryId, string $event, User $recipient): ?string
    {
        if ($statusHistoryId === null) {
            return null;
        }

        return "task-status:{$statusHistoryId}:{$event}:recipient:{$recipient->id}";
    }
}
