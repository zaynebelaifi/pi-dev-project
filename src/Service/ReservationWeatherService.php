<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ReservationWeatherService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly float $latitude,
        private readonly float $longitude,
        private readonly string $apiKey,
    ) {
    }

    public function getDailyState(\DateTimeInterface $date): ?array
    {
        try {
            if (trim($this->apiKey) !== '') {
                $openWeather = $this->getOpenWeatherDailyState($date);
                if ($openWeather !== null) {
                    return $openWeather;
                }
            }

            $openMeteo = $this->getOpenMeteoDailyState($date);
            if ($openMeteo !== null) {
                return $openMeteo;
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function getOpenWeatherDailyState(\DateTimeInterface $date): ?array
    {
        $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/forecast', [
            'query' => [
                'lat' => $this->latitude,
                'lon' => $this->longitude,
                'appid' => $this->apiKey,
                'units' => 'metric',
            ],
            'timeout' => 8,
        ]);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $payload = $response->toArray(false);
        $items = $payload['list'] ?? null;
        if (!is_array($items)) {
            return null;
        }

        $target = $date->format('Y-m-d');
        $matched = [];

        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['dt_txt'])) {
                continue;
            }

            $stamp = (string) $item['dt_txt'];
            if (!str_starts_with($stamp, $target)) {
                continue;
            }

            $matched[] = $item;
        }

        if ($matched === []) {
            return null;
        }

        $minTemps = [];
        $maxTemps = [];
        $precip = [];
        $feelsLikeTemps = [];
        $humidityValues = [];
        $windSpeeds = [];
        $windGusts = [];
        $windDirections = [];
        $weatherIds = [];
        $weatherDescriptions = [];

        foreach ($matched as $item) {
            if (isset($item['main']['temp_min'])) {
                $minTemps[] = (float) $item['main']['temp_min'];
            }
            if (isset($item['main']['temp_max'])) {
                $maxTemps[] = (float) $item['main']['temp_max'];
            }
            if (isset($item['pop'])) {
                $precip[] = (int) round(((float) $item['pop']) * 100);
            }
            if (isset($item['main']['feels_like'])) {
                $feelsLikeTemps[] = (float) $item['main']['feels_like'];
            }
            if (isset($item['main']['humidity'])) {
                $humidityValues[] = (int) $item['main']['humidity'];
            }
            if (isset($item['wind']['speed'])) {
                $windSpeeds[] = (float) $item['wind']['speed'];
            }
            if (isset($item['wind']['gust'])) {
                $windGusts[] = (float) $item['wind']['gust'];
            }
            if (isset($item['wind']['deg'])) {
                $windDirections[] = (int) $item['wind']['deg'];
            }
            $weather = $item['weather'][0] ?? null;
            if (is_array($weather)) {
                if (isset($weather['id'])) {
                    $weatherIds[] = (int) $weather['id'];
                }
                if (isset($weather['description'])) {
                    $weatherDescriptions[] = (string) $weather['description'];
                }
            }
        }

        $dominantWeatherId = $weatherIds === [] ? -1 : array_count_values($weatherIds);
        if (is_array($dominantWeatherId) && $dominantWeatherId !== []) {
            arsort($dominantWeatherId);
            $dominantWeatherId = (int) array_key_first($dominantWeatherId);
        } else {
            $dominantWeatherId = -1;
        }

        $mapped = $this->mapOpenWeatherCodeToState($dominantWeatherId);

        return [
            'date' => $target,
            'state' => $mapped['state'],
            'description' => $weatherDescriptions !== [] ? $weatherDescriptions[0] : $mapped['description'],
            'weather_code' => $dominantWeatherId,
            'temperature_min' => $minTemps !== [] ? min($minTemps) : null,
            'temperature_max' => $maxTemps !== [] ? max($maxTemps) : null,
            'feels_like' => $feelsLikeTemps !== [] ? (float) round(array_sum($feelsLikeTemps) / count($feelsLikeTemps), 1) : null,
            'precipitation_probability' => $precip !== [] ? max($precip) : null,
            'humidity' => $humidityValues !== [] ? (int) round(array_sum($humidityValues) / count($humidityValues)) : null,
            'wind_speed' => $windSpeeds !== [] ? (float) round((array_sum($windSpeeds) / count($windSpeeds)) * 3.6, 1) : null,
            'wind_gust' => $windGusts !== [] ? (float) round((array_sum($windGusts) / count($windGusts)) * 3.6, 1) : null,
            'wind_direction' => $this->formatWindDirection($this->averageWindDirection($windDirections)),
        ];
    }

    private function getOpenMeteoDailyState(\DateTimeInterface $date): ?array
    {
            $response = $this->httpClient->request('GET', 'https://api.open-meteo.com/v1/forecast', [
                'query' => [
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude,
                    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,apparent_temperature_max,precipitation_probability_max,wind_speed_10m_max,wind_direction_10m_dominant,relative_humidity_2m_mean',
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
            $feelsLikeTemps = $daily['apparent_temperature_max'] ?? [];
            $precip = $daily['precipitation_probability_max'] ?? [];
            $windSpeeds = $daily['wind_speed_10m_max'] ?? [];
            $windGusts = $daily['wind_gusts_10m_max'] ?? [];
            $windDirections = $daily['wind_direction_10m_dominant'] ?? [];
            $humidityValues = $daily['relative_humidity_2m_mean'] ?? [];

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
                'feels_like' => isset($feelsLikeTemps[$index]) ? (float) round((float) $feelsLikeTemps[$index], 1) : null,
                'precipitation_probability' => isset($precip[$index]) ? (int) $precip[$index] : null,
                'humidity' => isset($humidityValues[$index]) ? (int) round((float) $humidityValues[$index]) : null,
                'wind_speed' => isset($windSpeeds[$index]) ? (float) round((float) $windSpeeds[$index], 1) : null,
                'wind_gust' => isset($windGusts[$index]) ? (float) round((float) $windGusts[$index], 1) : null,
                'wind_direction' => isset($windDirections[$index]) ? $this->formatWindDirection((int) round((float) $windDirections[$index])) : null,
            ];
    }

        private function mapOpenWeatherCodeToState(int $code): array
        {
            return match (true) {
                $code === 800 => ['state' => 'Clear', 'description' => 'Clear sky'],
                in_array($code, [801, 802], true) => ['state' => 'Partly Cloudy', 'description' => 'Mostly clear to partly cloudy'],
                $code === 803 || $code === 804 => ['state' => 'Cloudy', 'description' => 'Overcast sky'],
                $code >= 200 && $code < 300 => ['state' => 'Stormy', 'description' => 'Thunderstorm risk'],
                $code >= 300 && $code < 400 => ['state' => 'Drizzle', 'description' => 'Light drizzle possible'],
                $code >= 500 && $code < 600 => ['state' => 'Rainy', 'description' => 'Rain showers likely'],
                $code >= 600 && $code < 700 => ['state' => 'Snowy', 'description' => 'Snow showers possible'],
                $code >= 700 && $code < 800 => ['state' => 'Foggy', 'description' => 'Fog or mist likely'],
                default => ['state' => 'Uncertain', 'description' => 'Weather state is uncertain'],
            };
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

    private function averageWindDirection(array $degrees): ?int
    {
        $degrees = array_values(array_filter($degrees, static fn ($value) => is_numeric($value)));
        if ($degrees === []) {
            return null;
        }

        $sin = 0.0;
        $cos = 0.0;

        foreach ($degrees as $degree) {
            $radians = deg2rad(((float) $degree) % 360);
            $sin += sin($radians);
            $cos += cos($radians);
        }

        if ($sin === 0.0 && $cos === 0.0) {
            return null;
        }

        $angle = rad2deg(atan2($sin / count($degrees), $cos / count($degrees)));
        $normalized = (int) round(fmod($angle + 360.0, 360.0));

        return $normalized === 360 ? 0 : $normalized;
    }

    private function formatWindDirection(?int $degrees): ?string
    {
        if ($degrees === null) {
            return null;
        }

        $sectors = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
        $index = (int) floor(((($degrees % 360) + 11.25) / 22.5));

        return sprintf('%s %d°', $sectors[$index % 16], $degrees % 360);
    }
}
