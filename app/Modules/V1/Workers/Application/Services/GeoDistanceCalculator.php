<?php

namespace App\Modules\V1\Workers\Application\Services;

class GeoDistanceCalculator
{
    public const EARTH_RADIUS_KM = 6371.0088;

    public function haversineKilometers(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);

        $fromLatitude = deg2rad($fromLatitude);
        $toLatitude = deg2rad($toLatitude);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos($fromLatitude) * cos($toLatitude) * sin($longitudeDelta / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1, sqrt($haversine)));
    }

    public function boundingBox(float $latitude, float $longitude, float $radiusKilometers): array
    {
        $latitudeDelta = rad2deg($radiusKilometers / self::EARTH_RADIUS_KM);
        $longitudeDelta = rad2deg($radiusKilometers / self::EARTH_RADIUS_KM / max(cos(deg2rad($latitude)), 0.000001));

        return [
            'min_latitude' => max(-90, $latitude - $latitudeDelta),
            'max_latitude' => min(90, $latitude + $latitudeDelta),
            'min_longitude' => max(-180, $longitude - $longitudeDelta),
            'max_longitude' => min(180, $longitude + $longitudeDelta),
        ];
    }
}
