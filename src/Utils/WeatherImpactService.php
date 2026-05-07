<?php

namespace App\Utils;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WeatherImpactService
{
    private const OPEN_METEO_URL = 'https://api.open-meteo.com/v1/forecast';
    private const OPEN_WEATHER_MAP_URL = 'https://api.openweathermap.org/data/2.5/weather';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(service: 'cache.app')]
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly string $openWeatherMapApiKey,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getWeatherImpact(): array
    {
        $lat = (float) $this->getEnv('WEATHER_LATITUDE', '36.8065');
        $lon = (float) $this->getEnv('WEATHER_LONGITUDE', '10.1815');
        $provider = trim($this->openWeatherMapApiKey) !== '' ? 'open_weather_map' : 'open_meteo';

        $cacheKey = sprintf('weather.%s.%s.%s', $provider, number_format($lat, 4, '.', ''), number_format($lon, 4, '.', ''));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($lat, $lon): array {
            $item->expiresAfter(600);

            try {
<<<<<<< Updated upstream
                $response = $this->httpClient->request('GET', 'https://api.open-meteo.com/v1/forecast', [
                    'query' => [
                        'latitude' => $lat,
                        'longitude' => $lon,
                        'current' => 'temperature_2m',
                        'timezone' => 'auto',
                    ],
                    'timeout' => 8,
                ]);

                $payload = $response->toArray(false);
                $temperature = isset($payload['current']['temperature_2m']) ? (float) $payload['current']['temperature_2m'] : null;

                if (null === $temperature) {
                    return $this->fallbackWeather('Temperature data unavailable from API response.');
=======
                if (trim($this->openWeatherMapApiKey) !== '') {
                    $openWeatherImpact = $this->fetchOpenWeatherImpact($lat, $lon);
                    if ($openWeatherImpact !== null) {
                        return $openWeatherImpact;
                    }
>>>>>>> Stashed changes
                }

                $openMeteoImpact = $this->fetchOpenMeteoImpact($lat, $lon);
                if ($openMeteoImpact !== null) {
                    return $openMeteoImpact;
                }

                return $this->fallbackWeather('Temperature data unavailable from weather providers.');
            } catch (\Throwable $e) {
                $this->logger->warning('Weather API request failed, using fallback.', [
                    'message' => $e->getMessage(),
                ]);

                return $this->fallbackWeather($e->getMessage());
            }
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchOpenWeatherImpact(float $lat, float $lon): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::OPEN_WEATHER_MAP_URL, [
                'query' => [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $this->openWeatherMapApiKey,
                    'units' => 'metric',
                ],
                'timeout' => 8,
                'max_duration' => 8,
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->warning('OpenWeatherMap request returned a non-success status code.', [
                    'statusCode' => $response->getStatusCode(),
                ]);

                return null;
            }

            $payload = $response->toArray(false);
            $temperature = isset($payload['main']['temp']) ? (float) $payload['main']['temp'] : null;

            if (null === $temperature) {
                return null;
            }

            $mapped = $this->mapTemperatureToImpact($temperature);

            return [
                'temperature' => $temperature,
                'demandMultiplier' => $mapped['demandMultiplier'],
                'expiryAcceleration' => $mapped['expiryAcceleration'],
                'statusLabel' => $mapped['statusLabel'],
                'statusClass' => $mapped['statusClass'],
                'isFallback' => false,
                'source' => 'openweathermap',
                'fetchedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i'),
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('OpenWeatherMap request failed, falling back to Open-Meteo.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchOpenMeteoImpact(float $lat, float $lon): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::OPEN_METEO_URL, [
                'query' => [
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'current' => 'temperature_2m',
                    'timezone' => 'auto',
                ],
                'timeout' => 8,
                'max_duration' => 8,
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->warning('Open-Meteo request returned a non-success status code.', [
                    'statusCode' => $response->getStatusCode(),
                ]);

                return null;
            }

            $payload = $response->toArray(false);
            $temperature = isset($payload['current']['temperature_2m']) ? (float) $payload['current']['temperature_2m'] : null;

            if (null === $temperature) {
                return null;
            }

            $mapped = $this->mapTemperatureToImpact($temperature);

            return [
                'temperature' => $temperature,
                'demandMultiplier' => $mapped['demandMultiplier'],
                'expiryAcceleration' => $mapped['expiryAcceleration'],
                'statusLabel' => $mapped['statusLabel'],
                'statusClass' => $mapped['statusClass'],
                'isFallback' => false,
                'source' => 'open-meteo',
                'fetchedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i'),
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('Open-Meteo request failed.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, float|string>
     */
    private function mapTemperatureToImpact(float $temperature): array
    {
        if ($temperature >= 32) {
            return [
                'demandMultiplier' => 1.24,
                'expiryAcceleration' => 1.34,
                'statusLabel' => 'Heat Stress',
                'statusClass' => 'wx-hot',
            ];
        }

        if ($temperature >= 26) {
            return [
                'demandMultiplier' => 1.14,
                'expiryAcceleration' => 1.22,
                'statusLabel' => 'Warm Surge',
                'statusClass' => 'wx-warm',
            ];
        }

        if ($temperature >= 18) {
            return [
                'demandMultiplier' => 1.00,
                'expiryAcceleration' => 1.00,
                'statusLabel' => 'Balanced',
                'statusClass' => 'wx-balanced',
            ];
        }

        if ($temperature >= 10) {
            return [
                'demandMultiplier' => 0.93,
                'expiryAcceleration' => 0.94,
                'statusLabel' => 'Cool Drift',
                'statusClass' => 'wx-cool',
            ];
        }

        return [
            'demandMultiplier' => 0.86,
            'expiryAcceleration' => 0.88,
            'statusLabel' => 'Cold Slowdown',
            'statusClass' => 'wx-cold',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackWeather(string $reason): array
    {
        return [
            'temperature' => null,
            'demandMultiplier' => 1.00,
            'expiryAcceleration' => 1.00,
            'statusLabel' => 'Weather Unavailable',
            'statusClass' => 'wx-unknown',
            'isFallback' => true,
            'source' => 'fallback',
            'reason' => $reason,
            'fetchedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i'),
        ];
    }

    private function getEnv(string $key, string $default): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if (false === $value || null === $value || '' === trim((string) $value)) {
            return $default;
        }

        return (string) $value;
    }
}
