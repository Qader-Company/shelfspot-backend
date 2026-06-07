<?php

namespace App\Modules\V1\CompaniesWallets\Domain\Models;

use App\Modules\Shared\Support\Traits\BelongsToCompany;
use App\Modules\V1\CompaniesWallets\Domain\ValueObjects\CompanyWalletTransactionTypeEnum;
use App\Modules\V1\Users\Domain\Models\User;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'company_id',
    'type',
    'amount',
    'balance_after',
    'performed_by',
    'description',
    'reference_type',
    'reference_id'
])]

class CompanyWalletTransaction extends Model
{
    use BelongsToCompany, Filterable;
    protected $casts = [
        'type' => CompanyWalletTransactionTypeEnum::class,
    ];

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
