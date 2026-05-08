<?php

namespace App\Contract;

interface WeatherServiceInterface
{
    public function getCurrentWeather(float $lat, float $lng): array;
}
