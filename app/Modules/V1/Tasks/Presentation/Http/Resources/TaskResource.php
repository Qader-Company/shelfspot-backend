<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Resources;

use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Presentation\Http\Resources\WorkerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userType = $request->user()?->type;
        $isWorker = $userType->value === 'worker';
        $isCompany = $userType->value === 'company';
        $isAdmin = $userType->value === 'admin';

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
            //            'execution_time' => $this->execution_time?->format('H:i:s'),
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'location_name' => $this->location_name,
                'address' => $this->address,
            ],
            'total_price' => (int) $this->total_price,
            'notes' => $this->notes,
            'status' => $isCompany ? $this->companyFacingStatus() : $this->status->value,
            'status_label' => __(
                'enums.task_status.'.($isCompany ? $this->companyFacingStatus() : $this->status->value)
            ),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'charged_at' => $this->charged_at?->toDateTimeString(),
            'accepted_at' => $this->accepted_at?->toDateTimeString(),
            'start_deadline_at' => $this->when($isWorker, $this->start_deadline_at?->toDateTimeString()),
            'start_deadline_extension_minutes' => $this->when($isWorker, $this->start_deadline_extension_minutes),
            'start_deadline_extended_at' => $this->when($isWorker, $this->start_deadline_extended_at?->toDateTimeString()),
            'started_at' => $this->started_at?->toDateTimeString(),
            'expected_completion_at' => $this->expected_completion_at?->toDateTimeString(),
            'in_progress_overdue_at' => $this->in_progress_overdue_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'rejected_at' => $this->rejected_at?->toDateTimeString(),
            'rejection_reason' => $this->rejection_reason,
            'company_accepted_at' => $this->company_accepted_at?->toDateTimeString(),
            'feedback' => $this->when($isCompany || $isAdmin, $this->feedback),
            'auto_accept_at' => $this->auto_accept_at?->toDateTimeString(),
            'reopened_at' => $this->reopened_at?->toDateTimeString(),
            'reopen_deadline_at' => $this->reopen_deadline_at?->toDateTimeString(),
            'reopen_reason' => $this->reopen_reason,
            'failure_reason' => $this->failure_reason?->value,
            'progress' => $this->progress(),
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
        if ($this->status === TaskStatusEnum::WORKER_CANCELLED) {
            return TaskStatusEnum::IN_PROGRESS->value;
        }

        return $this->status->value;
    }

    private function progress(): array
    {
        if (! $this->relationLoaded('services')) {
            return [
                'total_services' => 0,
                'completed_services' => 0,
                'remaining_services' => 0,
                'percentage' => 0,
            ];
        }

        $total = $this->services->count();
        $completed = $this->services->filter(
            fn ($service) => $service->status?->value === 'completed'
        )->count();

        return [
            'total_services' => $total,
            'completed_services' => $completed,
            'remaining_services' => max(0, $total - $completed),
            'percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        ];
    }
}
