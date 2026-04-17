<?php

namespace App\Utils;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherImpactService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(service: 'cache.app')]
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getWeatherImpact(): array
    {
        $lat = (float) $this->getEnv('WEATHER_LATITUDE', '36.8065');
        $lon = (float) $this->getEnv('WEATHER_LONGITUDE', '10.1815');

        $cacheKey = sprintf('weather.open_meteo.%s.%s', number_format($lat, 4, '.', ''), number_format($lon, 4, '.', ''));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($lat, $lon): array {
            $item->expiresAfter(600);

            try {
                $response = $this->httpClient->request('GET', 'https://api.open-meteo.com/v1/forecast', [
                    'query' => [
                        'latitude' => $lat,
                        'longitude' => $lon,
                        'current' => 'temperature_2m',
                        'timezone' => 'auto',
                    ],
                    'timeout' => 8,
                    'max_duration' => 8,
                ]);

                $payload = $response->toArray(false);
                $temperature = isset($payload['current']['temperature_2m']) ? (float) $payload['current']['temperature_2m'] : null;

                if (null === $temperature) {
                    return $this->fallbackWeather('Temperature data unavailable from API response.');
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
                $this->logger->warning('Weather API request failed, using fallback.', [
                    'message' => $e->getMessage(),
                ]);

                return $this->fallbackWeather($e->getMessage());
            }
        });
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
