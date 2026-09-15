<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Resources;

use App\Modules\V1\Stores\Presentation\Http\Resources\StoreResource;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentTypeEnum;
use App\Modules\V1\Tasks\Presentation\Http\Resources\Concerns\IncludesTaskStartTiming;
use App\Modules\V1\Tasks\Presentation\Http\Resources\Concerns\IncludesWorkerStoreDistance;
use App\Modules\V1\Workers\Presentation\Http\Resources\WorkerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    use IncludesTaskStartTiming, IncludesWorkerStoreDistance;

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
            'execution_window' => $this->execution_window_from === null || $this->execution_window_to === null
                ? null
                : [
                    'from' => $this->execution_window_from->format('H:i'),
                    'to' => $this->execution_window_to->format('H:i'),
                ],
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'location_name' => $this->location_name,
                'address' => $this->address,
            ],
            'store' => $this->whenLoaded('store', fn () => new StoreResource($this->store)),
            'total_price' => (int) $this->total_price,
            'notes' => $this->notes,
            'status' => $isCompany ? $this->companyFacingStatus() : $this->status->value,
            'status_label' => __(
                'enums.task_status.'.($isCompany ? $this->companyFacingStatus() : $this->status->value)
            ),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'expires_at' => $this->when($this->expires_at !== null, fn () => $this->expires_at->toISOString()),
            'start' => $this->startTiming($isWorker),
            'check_in_at' => $this->when($this->started_at !== null, fn () => $this->started_at->toISOString()),
            'expected_completion_at' => $this->when($this->expected_completion_at !== null, fn () => $this->expected_completion_at->toISOString()),
            'completed_at' => $this->when($this->completed_at !== null, fn () => $this->completed_at->toISOString()),
            'rejected_at' => $this->when($this->rejected_at !== null, fn () => $this->rejected_at->toISOString()),
            'rejection_reason' => $this->rejection_reason,
            'company_accepted_at' => $this->when($this->company_accepted_at !== null, fn () => $this->company_accepted_at->toISOString()),
            'feedback' => $this->when($isCompany || $isAdmin, $this->feedback),
            'auto_accept_at' => $this->when($this->auto_accept_at !== null, fn () => $this->auto_accept_at->toISOString()),
            'reopened_at' => $this->when($this->reopened_at !== null, fn () => $this->reopened_at->toISOString()),
            'reopen_deadline_at' => $this->when($this->reopen_deadline_at !== null, fn () => $this->reopen_deadline_at->toISOString()),
            'reopen_reason' => $this->reopen_reason,
            'failure_reason' => $this->failure_reason?->value,
            'assignment_type' => $this->when(
                $isWorker && $this->relationLoaded('currentWorkerAssignment'),
                fn () => $this->currentWorkerAssignment?->assignment_type?->value,
            ),
            'assignment_history' => $this->when(
                $isAdmin && $this->relationLoaded('workerAssignments'),
                fn () => TaskWorkerAssignmentResource::collection($this->workerAssignments)->resolve($request),
            ),
            'progress' => $this->progress(),
            'assigned_worker_id' => $this->when($isWorker || $this->relationLoaded('assignedWorker'), $this->assigned_worker_id),
            'assigned_worker' => new WorkerResource($this->whenLoaded('assignedWorker')),
            'distance_km' => $this->when($isWorker, fn () => $this->workerStoreDistanceKm($request)),
            'services' => TaskServiceResource::collection($this->whenLoaded('services')),
        ];
    }

    private function companyFacingStatus(): string
    {
        if (in_array($this->status, [TaskStatusEnum::WORKER_CANCELLED, TaskStatusEnum::REASSIGNED], true)
            || $this->isReassignedStart()) {
            return TaskStatusEnum::IN_PROGRESS->value;
        }

        return $this->status->value;
    }

    private function isReassignedStart(): bool
    {
        return $this->status === TaskStatusEnum::STARTED
            && $this->relationLoaded('currentWorkerAssignment')
            && $this->currentWorkerAssignment?->assignment_type === TaskWorkerAssignmentTypeEnum::REASSIGNED;
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
