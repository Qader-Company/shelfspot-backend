<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Application\Services\GeoDistanceCalculator;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartTaskUseCase
{
    public const START_GEOFENCE_RADIUS_KM = 0.2;

    public function __construct(
        private readonly GeoDistanceCalculator $geoDistanceCalculator,
        private readonly TaskStatusHistoryRecorder $statusHistoryRecorder,
        private readonly TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function execute(Task $task, Worker $worker, float $latitude, float $longitude): Task
    {
        return DB::transaction(function () use ($task, $worker, $latitude, $longitude) {
            /** @var Task $lockedTask */
            $lockedTask = $this->taskRepository->query()
                ->whereKey($task->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureTaskIsAvailableToBeStarted($lockedTask, $worker->id);

            $distance = $this->geoDistanceCalculator->haversineKilometers(
                fromLatitude: $latitude,
                fromLongitude: $longitude,
                toLatitude: (float) $lockedTask->latitude,
                toLongitude: (float) $lockedTask->longitude
            );

            if ($distance > self::START_GEOFENCE_RADIUS_KM) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.start_outside_geofence')]);
            }

            $fromStatus = $lockedTask->status;
            $lockedTask->forceFill([
                'status' => TaskStatusEnum::IN_PROGRESS,
                'started_at' => now(),
            ])->save();

            TaskStatusUpdated::dispatch(
                $lockedTask,
                $fromStatus,
                TaskStatusEnum::IN_PROGRESS,
                $worker,
                [
                    'worker_id' => $worker->id,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'distance_km' => round($distance, 6),
                    'geofence_radius_km' => self::START_GEOFENCE_RADIUS_KM,
                ]
            );

            return $lockedTask->refresh();
        });
    }

    private function ensureTaskIsAvailableToBeStarted(Task $lockedTask, $workerId): void
    {
        if ($lockedTask->status !== TaskStatusEnum::ACCEPTED) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.start_accepted_only')]);
        }

        if ((int) $lockedTask->assigned_worker_id !== (int) $workerId) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.worker_not_assigned')]);
        }

        if ($lockedTask->start_deadline_at !== null && now()->greaterThan($lockedTask->start_deadline_at)) {
            throw ValidationException::withMessages(['task' => __('tasks.validation.start_deadline_expired')]);
        }
    }
}
