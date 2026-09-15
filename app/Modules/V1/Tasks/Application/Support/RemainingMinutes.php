<?php

namespace App\Modules\V1\Tasks\Application\Support;

use Carbon\CarbonInterface;

final class RemainingMinutes
{
    public static function until(?CarbonInterface $deadline, ?CarbonInterface $at = null): ?int
    {
        if ($deadline === null) {
            return null;
        }

        $seconds = $deadline->getTimestamp() - ($at ?? now())->getTimestamp();

        return max(0, (int) ceil($seconds / 60));
    }
}
