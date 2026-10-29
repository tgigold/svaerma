<?php

declare(strict_types=1);

namespace App\Service\Osm;

final class Geo
{
    private const EARTH_RADIUS = 6_371_000.0;

    /** Great-circle distance in metres (haversine). */
    public static function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return 2 * self::EARTH_RADIUS * asin(min(1.0, sqrt($a)));
    }

    public static function validCoordinates(float $lat, float $lon): bool
    {
        return $lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180;
    }
}
