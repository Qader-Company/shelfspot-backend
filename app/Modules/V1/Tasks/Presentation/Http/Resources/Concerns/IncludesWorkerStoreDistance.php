<?php

namespace App\Modules\V1\Tasks\Presentation\Http\Resources\Concerns;

use App\Modules\V1\Workers\Application\Services\GeoDistanceCalculator;
use Illuminate\Http\Request;

trait IncludesWorkerStoreDistance
{
    private function workerStoreDistanceKm(Request $request): ?float
    {
        if (isset($this->distance_km)) {
            return round((float) $this->distance_km, 3);
        }

        $worker = $request->user()?->worker;

        if ($worker?->last_latitude === null || $worker?->last_longitude === null
            || $this->latitude === null || $this->longitude === null) {
            return null;
        }

        return round(app(GeoDistanceCalculator::class)->haversineKilometers(
            (float) $worker->last_latitude,
            (float) $worker->last_longitude,
            (float) $this->latitude,
            (float) $this->longitude,
        ), 3);
    }
}
