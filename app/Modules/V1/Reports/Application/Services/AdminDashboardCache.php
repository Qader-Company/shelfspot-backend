<?php

namespace App\Modules\V1\Reports\Application\Services;

use Illuminate\Support\Facades\Cache;

final class AdminDashboardCache
{
    private const PERIODS = ['week', 'month', 'year'];

    public static function key(string $period): string
    {
        return "admin-dashboard:{$period}";
    }

    public static function forget(): void
    {
        foreach (self::PERIODS as $period) {
            Cache::forget(self::key($period));
        }
    }
}
