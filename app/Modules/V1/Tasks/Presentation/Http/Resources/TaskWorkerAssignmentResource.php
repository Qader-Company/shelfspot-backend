<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskWorkerAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'worker' => [
                'id' => $this->worker_id,
                'name' => $this->whenLoaded('worker', fn () => $this->worker?->user?->name),
            ],
            'assignment_type' => $this->assignment_type?->value,
            'assigned_by' => $this->whenLoaded('assigner', fn () => [
                'id' => $this->assigner?->id,
                'name' => $this->assigner?->name,
            ]),
            'assigned_at' => $this->assigned_at?->toDateTimeString(),
            'unassigned_at' => $this->unassigned_at?->toDateTimeString(),
            'outcome' => $this->outcome?->value,
            'reason' => $this->reason,
        ];
    }
}
