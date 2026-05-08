<?php

namespace App\Contract;

interface RouteServiceInterface
{
    public function getRouteData(float $startLat, float $startLng, float $endLat, float $endLng): array;
}
