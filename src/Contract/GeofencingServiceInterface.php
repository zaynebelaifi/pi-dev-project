<?php

namespace App\Contract;

interface GeofencingServiceInterface
{
    public function isWithinZone(float $lat, float $lng, float $centerLat, float $centerLng, ?float $radiusKm = null): bool;
}
