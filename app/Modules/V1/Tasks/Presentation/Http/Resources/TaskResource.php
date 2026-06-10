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
            'created_by' => $this->whenLoaded('creator', $this->creator->name),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'charged_at' => $this->charged_at?->toDateTimeString(),
            'accepted_at' => $this->accepted_at?->toDateTimeString(),
            'started_at' => $this->started_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'declined_at' => $this->declined_at?->toDateTimeString(),
            'decline_reason' => $this->decline_reason,
            'distance_km' => $this->when(isset($this->distance_km), fn () => round((float) $this->distance_km, 3)),
            'services' => TaskServiceResource::collection($this->whenLoaded('services')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
