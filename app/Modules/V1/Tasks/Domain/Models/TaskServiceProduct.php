<?php

namespace App\Modules\V1\Tasks\Domain\Models;

use App\Modules\V1\Products\Domain\Models\Product;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_service_id',
    'product_id',
    'product_details',
])]
class TaskServiceProduct extends Model
{
    protected $casts = [
        'product_details' => 'array',
    ];

    public function taskService(): BelongsTo
    {
        return $this->belongsTo(TaskService::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
