<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Resources;

use App\Modules\V1\Stores\Presentation\Http\Resources\StoreResource;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskWorkerAssignmentTypeEnum;
use App\Modules\V1\Tasks\Presentation\Http\Resources\Concerns\IncludesTaskStartTiming;
use App\Modules\V1\Tasks\Presentation\Http\Resources\Concerns\IncludesWorkerStoreDistance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskListResource extends JsonResource
{
    use IncludesTaskStartTiming, IncludesWorkerStoreDistance;

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
            ],
            'store' => $this->whenLoaded('store', fn () => new StoreResource($this->store)),
            'total_price' => (float) $this->total_price,
            'status' => $isCompany ? $this->companyFacingStatus() : $this->status->value,
            'status_label' => __('enums.task_status.'.($isCompany ? $this->companyFacingStatus() : $this->status->value)),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'expires_at' => $this->when($this->expires_at !== null, fn () => $this->expires_at->toISOString()),
            'start' => $this->startTiming($isWorker),
            'check_in_at' => $this->when($this->started_at !== null, fn () => $this->started_at->toISOString()),
            'expected_completion_at' => $this->when($this->expected_completion_at !== null, fn () => $this->expected_completion_at->toISOString()),
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
            'distance_km' => $this->when($isWorker, fn () => $this->workerStoreDistanceKm($request)),
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
}
