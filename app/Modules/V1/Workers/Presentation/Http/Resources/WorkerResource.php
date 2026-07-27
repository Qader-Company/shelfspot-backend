<?php

namespace App\Modules\V1\Workers\Presentation\Http\Resources;

use App\Modules\V1\Tasks\Presentation\Http\Resources\TaskResource;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        [$worker, $user] = $this->resolveWorkerAndUser();
        $isOwnProfile = $request->user()?->id === $user?->id;

        return [
            'id' => $worker?->id,
            'name' => $user?->name,
            'email' => $user?->email,
            'phone' => $worker?->phone,
            'image' => $worker?->getFirstMediaUrl('image') ?: null,
            'deleted_at' => $worker?->deleted_at?->toISOString(),
            'type' => $user?->type?->value,
            'is_active' => (bool) $worker?->is_active,
            'distance_km' => $this->when(isset($worker->distance_km), fn () => round((float) $worker->distance_km, 3)),
            'last_location' => [
                'latitude' => $worker?->last_latitude,
                'longitude' => $worker?->last_longitude,
                'name' => $worker?->last_location_name,
                'updated_at' => $worker?->location_updated_at?->toISOString(),
            ],
            'task_counts' => $this->when(isset($worker->admin_task_counts), $worker->admin_task_counts),
            'in_progress_task_completion_percentage' => $this->when(
                isset($worker->admin_task_counts),
                $worker->in_progress_task_completion_percentage ?? null
            ),
            'assigned_tasks' => TaskResource::collection($this->whenLoaded('assignedTasks')),
            'priority_tasks' => $this->when(
                $isOwnProfile && $worker?->relationLoaded('priorityTasks'),
                fn () => WorkerPriorityTaskResource::collection($worker->priorityTasks)->resolve($request),
            ),
        ];
    }

    private function resolveWorkerAndUser(): array
    {
        if ($this->resource instanceof User) {
            $this->resource->loadMissing('worker');

            return [$this->resource->worker, $this->resource];
        }

        if ($this->resource instanceof Worker) {
            $this->resource->loadMissing('user');

            return [$this->resource, $this->resource->user];
        }

        return [null, null];
    }
}
