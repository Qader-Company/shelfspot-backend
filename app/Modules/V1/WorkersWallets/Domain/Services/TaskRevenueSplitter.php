<?php

namespace App\Modules\V1\WorkersWallets\Domain\Services;

class TaskRevenueSplitter
{
    public const WORKER_SHARE_PERCENTAGE = 50;

    public function split(float|int|string $total): array
    {
        $totalCents = (int) round(((float) $total) * 100);
        $workerCents = intdiv($totalCents * self::WORKER_SHARE_PERCENTAGE, 100);
        $platformCents = $totalCents - $workerCents;

        return [
            'worker_share_amount' => $workerCents / 100,
            'platform_share_amount' => $platformCents / 100,
            'worker_share_percentage' => self::WORKER_SHARE_PERCENTAGE,
        ];
    }
}
