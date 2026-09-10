<?php

namespace App\Modules\V1\Payments\Presentation\Http\Resources;

use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->id,
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', fn () => $this->company ? [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'email' => $this->company->email,
                'phone' => $this->company->phone,
            ] : null),
            'amount' => (float) $this->total_price,
            'settlement' => $this->settled_at === null ? null : [
                'worker_share_amount' => (float) $this->worker_share_amount,
                'platform_share_amount' => (float) $this->platform_share_amount,
                'worker_share_percentage' => (int) $this->worker_share_percentage,
                'settled_at' => $this->settled_at->toDateTimeString(),
            ],
            'direction' => $this->direction(),
            'status' => $this->statusValue(),
            'status_label' => $this->statusLabel(),
            'payment_status' => $this->payment_status?->value,
            'payment_status_label' => $this->payment_status?->label(),
            'date' => $this->charged_at?->toDateString() ?? $this->created_at?->toDateString(),
            'charged_at' => $this->charged_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    private function direction(): string
    {
        return $this->payment_status === TaskPaymentStatusEnum::REFUNDED ? 'outgoing' : 'incoming';
    }

    private function statusValue(): string
    {
        if ($this->payment_status === TaskPaymentStatusEnum::CHARGED) {
            return 'completed';
        }

        return $this->payment_status?->value ?? TaskPaymentStatusEnum::PENDING->value;
    }

    private function statusLabel(): string
    {
        if ($this->payment_status === TaskPaymentStatusEnum::CHARGED) {
            return __('api.completed');
        }

        return $this->payment_status?->label() ?? TaskPaymentStatusEnum::PENDING->label();
    }
}
