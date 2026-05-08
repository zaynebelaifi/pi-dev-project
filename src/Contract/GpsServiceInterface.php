<?php

namespace App\Contract;

interface GpsServiceInterface
{
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float;
}
