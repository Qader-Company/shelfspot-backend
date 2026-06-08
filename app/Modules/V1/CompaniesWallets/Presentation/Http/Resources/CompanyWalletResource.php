<?php

namespace App\Modules\V1\CompaniesWallets\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyWalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'amount' => $this->amount,
            'balance_after' => $this->balance_after,
            'performed_by' => $this->whenLoaded('performedBy', fn () => $this->performedBy ? [
                'id' => $this->performedBy->id,
                'name' => $this->performedBy->name,
                'email' => $this->performedBy->email,
            ] : null),
            'description' => $this->description,
//            'reference' => $this->reference_type && $this->reference_id ? [
//                'type' => $this->reference_type,
//                'id' => $this->reference_id,
//            ] : null,
            'created_at' => $this->created_at?->toDateString(),
            'updated_at' => $this->updated_at?->toDateString(),
        ];
    }
}
