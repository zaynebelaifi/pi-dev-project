<?php

namespace App\Service\Fleet;

use App\Contract\GeofencingServiceInterface;

class GeofencingService implements GeofencingServiceInterface
{
    public function __construct(
        private readonly float $defaultZoneRadius = 10.0,
    ) {}

    public function isWithinZone(float $lat, float $lng, float $centerLat, float $centerLng, ?float $radiusKm = null): bool
    {
        $radiusKm ??= $this->defaultZoneRadius;
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat - $centerLat);
        $dLon = deg2rad($lng - $centerLng);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($centerLat)) * cos(deg2rad($lat)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        return $distance <= $radiusKm;
    }
}
