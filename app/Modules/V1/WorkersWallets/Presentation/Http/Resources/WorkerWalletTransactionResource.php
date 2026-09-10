<?php

namespace App\Modules\V1\WorkersWallets\Presentation\Http\Resources;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\WorkersWallets\Domain\Models\WithdrawalRequest;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WorkerWalletTransactionTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerWalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reference = $this->relationLoaded('reference') ? $this->reference : null;

        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'direction' => $this->direction(),
            'amount' => (float) $this->amount,
            'balance_after' => (float) $this->balance_after,
            'description' => $this->description,
            'task_id' => $this->when(
                $reference instanceof Task,
                fn () => $this->reference_id,
            ),
            'withdrawal' => $this->when(
                $reference instanceof WithdrawalRequest,
                fn () => [
                    'id' => $reference->id,
                    'status' => $reference->status?->value,
                    'status_label' => $reference->status?->label(),
                    'method' => $reference->method?->value,
                    'method_label' => $reference->method?->label(),
                    'payout_details' => $reference->payout_details,
                    'processed_at' => $reference->processed_at?->toDateTimeString(),
                    'paid_at' => $reference->paid_at?->toDateTimeString(),
                    'rejection_reason' => $reference->rejection_reason,
                ],
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }

    private function direction(): string
    {
        return $this->type === WorkerWalletTransactionTypeEnum::WITHDRAWAL
            ? 'debit'
            : 'credit';
    }
}
