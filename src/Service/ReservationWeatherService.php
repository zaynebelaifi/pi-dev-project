<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ReservationWeatherService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly float $latitude,
        private readonly float $longitude,
    ) {
    }

    public function getDailyState(\DateTimeInterface $date): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.open-meteo.com/v1/forecast', [
                'query' => [
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude,
                    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
                    'timezone' => 'auto',
                    'forecast_days' => 14,
                ],
                'timeout' => 8,
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $payload = $response->toArray(false);
            $daily = $payload['daily'] ?? null;
            if (!is_array($daily)) {
                return null;
            }

            $times = $daily['time'] ?? [];
            $codes = $daily['weather_code'] ?? [];
            $maxTemps = $daily['temperature_2m_max'] ?? [];
            $minTemps = $daily['temperature_2m_min'] ?? [];
            $precip = $daily['precipitation_probability_max'] ?? [];

            $target = $date->format('Y-m-d');
            $index = array_search($target, $times, true);
            if ($index === false) {
                return null;
            }

            $code = (int) ($codes[$index] ?? -1);
            $mapped = $this->mapCodeToState($code);

            return [
                'date' => $target,
                'state' => $mapped['state'],
                'description' => $mapped['description'],
                'weather_code' => $code,
                'temperature_min' => isset($minTemps[$index]) ? (float) $minTemps[$index] : null,
                'temperature_max' => isset($maxTemps[$index]) ? (float) $maxTemps[$index] : null,
                'precipitation_probability' => isset($precip[$index]) ? (int) $precip[$index] : null,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function mapCodeToState(int $code): array
    {
        return match (true) {
            $code === 0 => ['state' => 'Clear', 'description' => 'Clear sky'],
            in_array($code, [1, 2], true) => ['state' => 'Partly Cloudy', 'description' => 'Mostly clear to partly cloudy'],
            $code === 3 => ['state' => 'Cloudy', 'description' => 'Overcast sky'],
            in_array($code, [45, 48], true) => ['state' => 'Foggy', 'description' => 'Fog or rime fog'],
            in_array($code, [51, 53, 55, 56, 57], true) => ['state' => 'Drizzle', 'description' => 'Light to dense drizzle'],
            in_array($code, [61, 63, 65, 66, 67, 80, 81, 82], true) => ['state' => 'Rainy', 'description' => 'Rain showers likely'],
            in_array($code, [71, 73, 75, 77, 85, 86], true) => ['state' => 'Snowy', 'description' => 'Snow showers possible'],
            in_array($code, [95, 96, 99], true) => ['state' => 'Stormy', 'description' => 'Thunderstorm risk'],
            default => ['state' => 'Uncertain', 'description' => 'Weather state is uncertain'],
        };
    }
}
