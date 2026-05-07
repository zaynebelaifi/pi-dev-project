<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OpenWeatherMapService
{
    private const BASE_URL = 'https://api.openweathermap.org/data/2.5/weather';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey,
    ) {
    }

    public function getCurrentByCity(string $city = 'Tunis'): ?array
    {
        if (trim($this->apiKey) === '') {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', self::BASE_URL, [
                'query' => [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'en',
                ],
                'timeout' => 5,
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray(false);

            $weatherMain = strtolower((string) ($data['weather'][0]['main'] ?? ''));
            $description = (string) ($data['weather'][0]['description'] ?? '');
            $temperature = (float) ($data['main']['temp'] ?? 0.0);

            return [
                'city' => $city,
                'main' => $weatherMain,
                'description' => $description,
                'temp' => $temperature,
                'is_rainy' => in_array($weatherMain, ['rain', 'drizzle', 'thunderstorm'], true),
                'is_hot' => $temperature >= 32,
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
