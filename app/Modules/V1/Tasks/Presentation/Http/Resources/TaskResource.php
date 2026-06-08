<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'date' => $this->date?->toDateString(),
            'execution_time' => $this->execution_time?->format('H:i:s'),
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'location_name' => $this->location_name,
                'address' => $this->address,
            ],
            'subtotal' => $this->subtotal,
            'total_price' => $this->total_price,
            'notes' => $this->notes,
            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,
            'created_by' => $this->created_by,
            'assigned_worker_id' => $this->assigned_worker_id,
            'expires_at' => $this->expires_at?->toISOString(),
            'charged_at' => $this->charged_at?->toISOString(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'declined_at' => $this->declined_at?->toISOString(),
            'decline_reason' => $this->decline_reason,
            'services' => TaskServiceResource::collection($this->whenLoaded('services')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
