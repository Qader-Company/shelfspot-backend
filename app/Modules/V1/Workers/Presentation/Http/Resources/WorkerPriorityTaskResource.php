<?php

namespace App\Modules\V1\Workers\Presentation\Http\Resources;

use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerPriorityTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $assignment = $this->resource->relationLoaded('currentWorkerAssignment')
            ? $this->resource->currentWorkerAssignment
            : null;

        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'action_required' => match ($this->status) {
                TaskStatusEnum::REASSIGNED => 'start',
                TaskStatusEnum::STARTED, TaskStatusEnum::REOPENED => 'check_in',
                default => null,
            },
            'assignment_type' => $assignment?->assignment_type?->value,
            'assigned_at' => $assignment?->assigned_at?->toDateTimeString(),
            'reopen_deadline_at' => $this->reopen_deadline_at?->toDateTimeString(),
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'location' => [
                'location_name' => $this->location_name,
                'address' => $this->address,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
        ];
    }
}
