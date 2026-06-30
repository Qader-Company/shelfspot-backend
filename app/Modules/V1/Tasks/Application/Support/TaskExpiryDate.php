<?php

namespace App\Modules\V1\Tasks\Application\Support;

use Illuminate\Support\Carbon;

class TaskExpiryDate
{
    public static function fromExecutionDate(string $executionDate): Carbon
    {
        return Carbon::parse($executionDate)->startOfDay()->addDay();
    }
}
