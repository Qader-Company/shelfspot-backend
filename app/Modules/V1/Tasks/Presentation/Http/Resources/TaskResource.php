<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Resources;


use App\Modules\V1\Workers\Presentation\Http\Resources\WorkerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userType = $request->user()?->type;
        $userTypeValue = $userType instanceof \BackedEnum ? $userType->value : $userType;
        $isWorker = $userTypeValue === 'worker';
        $isCompany = $userTypeValue === 'company';

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company?->id,
                'name' => $this->company?->name,
                'email' => $this->company?->email,
                'phone' => $this->company?->phone,
            ]),
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
            'status' => $isCompany ? $this->companyFacingStatus() : $this->status->value,
            'payment_status' => $this->payment_status->value,
            'created_by' => $this->whenLoaded('creator', $this->creator->name),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'charged_at' => $this->charged_at?->toDateTimeString(),
            'accepted_at' => $this->accepted_at?->toDateTimeString(),
            'start_deadline_at' => $this->when($isWorker, $this->start_deadline_at?->toDateTimeString()),
            'started_at' => $this->started_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'declined_at' => $this->declined_at?->toDateTimeString(),
            'decline_reason' => $this->decline_reason,
            'assigned_worker_id' => $this->when($isWorker || $this->relationLoaded('assignedWorker'), $this->assigned_worker_id),
            'assigned_worker' => new WorkerResource($this->whenLoaded('assignedWorker')),
            'distance_km' => $this->when(isset($this->distance_km), fn () => round((float) $this->distance_km, 3)),
            'services' => TaskServiceResource::collection($this->whenLoaded('services')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    private function companyFacingStatus(): string
    {
        return match ($this->status->value) {
            'accepted', 'worker_cancelled' => 'in_progress',
            'company_deleted' => 'pending',
            'admin_deleted' => 'failed',
            default => $this->status->value,
        };
    }
}
