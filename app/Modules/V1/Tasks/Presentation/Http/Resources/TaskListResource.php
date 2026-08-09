<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userType = $request->user()?->type?->value;
        $isWorker = $userType === 'worker';
        $isCompany = $userType === 'company';

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company?->id,
                'name' => $this->company?->name,
            ]),
            'date' => $this->date?->toDateString(),
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'location_name' => $this->location_name,
            ],
            'total_price' => (float) $this->total_price,
            'status' => $isCompany ? $this->companyFacingStatus() : $this->status->value,
            'status_label' => __('enums.task_status.'.($isCompany ? $this->companyFacingStatus() : $this->status->value)),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'start_deadline_at' => $this->when($isWorker, $this->start_deadline_at?->toDateTimeString()),
            'expected_completion_at' => $this->expected_completion_at?->toDateTimeString(),
            'assignment_type' => $this->when(
                $isWorker && $this->relationLoaded('currentWorkerAssignment'),
                fn () => $this->currentWorkerAssignment?->assignment_type?->value,
            ),
            'assigned_worker' => $this->whenLoaded('assignedWorker', function () {
                $worker = $this->assignedWorker;
                $user = $worker?->relationLoaded('user') ? $worker->user : null;

                return [
                    'id' => $worker?->id,
                    'name' => $user?->name,
                ];
            }),
            'services_count' => $this->when(isset($this->services_count), (int) $this->services_count),
            'distance_km' => $this->when(isset($this->distance_km), fn () => round((float) $this->distance_km, 3)),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    private function companyFacingStatus(): string
    {
        return $this->status->value === 'worker_cancelled' ? 'in_progress' : $this->status->value;
    }
}
