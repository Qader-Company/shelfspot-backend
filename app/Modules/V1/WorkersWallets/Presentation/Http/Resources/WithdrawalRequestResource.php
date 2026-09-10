<?php

namespace App\Modules\V1\WorkersWallets\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'worker_id' => $this->worker_id,
            'worker' => $this->whenLoaded('worker', fn () => $this->worker ? [
                'id' => $this->worker->id,
                'name' => $this->worker->user?->name,
                'email' => $this->worker->user?->email,
                'phone' => $this->worker->phone,
            ] : null),
            'amount' => (float) $this->amount,
            'method' => $this->method?->value,
            'method_label' => $this->method?->label(),
            'payout_details' => $this->payout_details,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'processed_by' => $this->whenLoaded('processedBy', fn () => $this->processedBy ? [
                'id' => $this->processedBy->id,
                'name' => $this->processedBy->name,
            ] : null),
            'processed_at' => $this->processed_at?->toDateTimeString(),
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
