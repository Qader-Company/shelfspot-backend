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
            'withdrawal_id' => $this->when(
                $reference instanceof WithdrawalRequest,
                fn () => $this->reference_id,
            ),
            'withdrawal_status' => $this->when(
                $reference instanceof WithdrawalRequest,
                fn () => $reference->status?->value,
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
