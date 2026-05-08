<?php

declare(strict_types=1);

namespace App\Service\Fleet;

use App\Contract\WeatherServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * WeatherService — fetches current weather using Open-Meteo (100% free, no API key).
 *
 * Open-Meteo API: https://open-meteo.com/
 * No key required. Uses WMO weather codes.
 *
 * If the network call fails, returns a safe static "Clear / 22°C" fallback.
 */
class WeatherService implements WeatherServiceInterface
{
    /** Open-Meteo free forecast endpoint. */
    private const OPEN_METEO_URL = 'https://api.open-meteo.com/v1/forecast';

    /**
     * WMO Weather Codes → human-readable condition.
     * See: https://open-meteo.com/en/docs#weathervariables
     */
    private const WMO_CONDITIONS = [
        0  => 'Clear',
        1  => 'Mostly Clear',
        2  => 'Partly Cloudy',
        3  => 'Overcast',
        45 => 'Fog',
        48 => 'Fog',
        51 => 'Drizzle',
        53 => 'Drizzle',
        55 => 'Heavy Drizzle',
        61 => 'Rain',
        63 => 'Rain',
        65 => 'Heavy Rain',
        71 => 'Snow',
        73 => 'Snow',
        75 => 'Heavy Snow',
        77 => 'Snow Grains',
        80 => 'Showers',
        81 => 'Showers',
        82 => 'Heavy Showers',
        95 => 'Thunderstorm',
        96 => 'Thunderstorm',
        99 => 'Thunderstorm',
    ];

    /** Conditions considered "bad" for ETA adjustment. */
    private const BAD_CONDITIONS = ['Rain', 'Heavy Rain', 'Drizzle', 'Heavy Drizzle', 'Showers',
                                    'Heavy Showers', 'Snow', 'Heavy Snow', 'Snow Grains',
                                    'Thunderstorm', 'Fog'];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface     $logger,
        private readonly string              $apiKey = '', // Reserved for OpenWeather upgrade
    ) {}

    // ── Public API ──────────────────────────────────────────────────────────

    /**
     * Fetch current weather for a coordinate pair.
     *
     * @return array{condition: string, temp: float, wind: float, icon: string, code: int, isBad: bool, multiplier: float}
     */
    public function getCurrentWeather(float $lat, float $lng): array
    {
        try {
            $response = $this->httpClient->request('GET', self::OPEN_METEO_URL, [
                'query'   => [
                    'latitude'        => $lat,
                    'longitude'       => $lng,
                    'current_weather' => 'true',
                    'windspeed_unit'  => 'ms',
                ],
                'timeout' => 4,
            ]);

            $data    = $response->toArray();
            $current = $data['current_weather'] ?? [];

            $code      = (int) ($current['weathercode'] ?? 0);
            $condition = self::WMO_CONDITIONS[$code] ?? 'Clear';
            $temp      = (float) ($current['temperature'] ?? 22.0);
            $wind      = (float) ($current['windspeed']   ?? 0.0);

            return $this->buildResult($condition, $temp, $wind, $code);
        } catch (\Throwable $e) {
            $this->logger->info('[Weather] Open-Meteo unavailable, using static fallback: ' . $e->getMessage());
            return $this->getFallback();
        }
    }

    /**
     * Returns true if weather conditions warrant an ETA adjustment.
     */
    public function isBadWeather(array $weather): bool
    {
        return $weather['isBad'] ?? false;
    }

    /**
     * Returns the ETA multiplier: 1.0 (normal) or 1.3 (bad weather).
     */
    public function getETAMultiplier(array $weather): float
    {
        return $this->isBadWeather($weather) ? 1.3 : 1.0;
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function buildResult(string $condition, float $temp, float $wind, int $code): array
    {
        $isBad = in_array($condition, self::BAD_CONDITIONS, true);
        return [
            'condition'  => $condition,
            'temp'       => $temp,
            'wind'       => $wind,
            'code'       => $code,
            'icon'       => $this->resolveIcon($condition),
            'isBad'      => $isBad,
            'multiplier' => $isBad ? 1.3 : 1.0,
        ];
    }

    private function getFallback(): array
    {
        return $this->buildResult('Clear', 22.0, 0.0, 0);
    }

    private function resolveIcon(string $condition): string
    {
        return match (true) {
            str_contains($condition, 'Thunder')                      => '⛈',
            str_contains($condition, 'Snow') || $condition === 'Snow Grains' => '❄️',
            str_contains($condition, 'Heavy Rain') || str_contains($condition, 'Heavy Shower') => '🌧',
            str_contains($condition, 'Rain') || str_contains($condition, 'Drizzle') || str_contains($condition, 'Shower') => '🌦',
            str_contains($condition, 'Fog')                          => '🌫',
            str_contains($condition, 'Overcast')                     => '☁️',
            str_contains($condition, 'Cloudy')                       => '⛅',
            default                                                  => '☀️',
        };
    }
}
