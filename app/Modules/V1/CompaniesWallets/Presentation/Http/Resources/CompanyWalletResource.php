<?php

namespace App\Modules\V1\CompaniesWallets\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyWalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type->label(),
            'amount' => $this->amount,
            'balance_after' => $this->balance_after,
            'performed_by' => $this->performedBy->name,
            'description' => $this->description,
        ];
    }
}
