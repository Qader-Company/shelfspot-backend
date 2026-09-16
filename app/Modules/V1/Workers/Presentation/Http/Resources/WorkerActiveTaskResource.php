<?php

namespace App\Modules\V1\Workers\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerActiveTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $assignment = $this->resource->relationLoaded('currentWorkerAssignment')
            ? $this->resource->currentWorkerAssignment
            : null;

        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'assignment_type' => $assignment?->assignment_type?->value,
        ];
    }
}
