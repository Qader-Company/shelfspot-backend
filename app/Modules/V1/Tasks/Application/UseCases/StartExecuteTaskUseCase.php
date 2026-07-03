<?php

namespace App\Modules\V1\Tasks\Application\UseCases;

use App\Events\TaskStatusUpdated;
use App\Modules\V1\Tasks\Application\Services\TaskActionsRules\CanExecuteTaskRule;
use App\Modules\V1\Tasks\Application\Services\TaskStatusHistoryRecorder;
use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Application\Services\GeoDistanceCalculator;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartExecuteTaskUseCase
{
    public const START_GEOFENCE_RADIUS_KM = 0.2;

    public function __construct(
        private readonly GeoDistanceCalculator $geoDistanceCalculator,
        private readonly TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function execute(Task $task, Worker $worker, float $latitude, float $longitude): Task
    {
        return DB::transaction(function () use ($task, $worker, $latitude, $longitude) {
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);
            $fromStatus = $lockedTask->status;

            CanExecuteTaskRule::validate($lockedTask, $worker);

            $distance = $this->geoDistanceCalculator->haversineKilometers(
                fromLatitude: $latitude,
                fromLongitude: $longitude,
                toLatitude: (float) $lockedTask->latitude,
                toLongitude: (float) $lockedTask->longitude
            );

            if ($distance > self::START_GEOFENCE_RADIUS_KM) {
                throw ValidationException::withMessages(['task' => __('tasks.validation.start_outside_geofence')]);
            }

            $now = now();

            $lockedTask->forceFill([
                'status' => TaskStatusEnum::IN_PROGRESS,
                'started_at' => $now,
                'expected_completion_at' => $now->copy()->addMinutes((int) $lockedTask->estimated_duration_minutes),
                'in_progress_overdue_at' => null,
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
}
