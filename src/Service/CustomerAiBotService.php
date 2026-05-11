<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CustomerAiBotService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $endpoint,
        private readonly int $timeout = 20,
    ) {
    }

    public function ask(string $question, array $context = []): string
    {
        $question = trim($question);
        if ($question === '') {
            return 'Ask me about menu items, delivery, reservations, or opening hours.';
        }

        try {
            $prompt = $this->buildPrompt($question, $context);
            $url = rtrim($this->endpoint, '/').'/'.rawurlencode($prompt);

            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Accept' => 'text/plain, application/json',
                ],
                'timeout' => $this->timeout,
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('AI provider returned a non-200 response.');
            }

            $body = trim($response->getContent(false));
            if ($body === '') {
                return $this->fallback($question, $context);
            }

            if (str_starts_with($body, '{') || str_starts_with($body, '[')) {
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    $text = $decoded['text'] ?? $decoded['response'] ?? $decoded['output'] ?? '';
                    if (is_string($text) && trim($text) !== '') {
                        return $this->normalize($text);
                    }
                }
            }

            return $this->normalize($body);
        } catch (\Throwable $e) {
            $this->logger->warning('Customer AI bot fallback used.', ['message' => $e->getMessage()]);

            return $this->fallback($question, $context);
        }
    }

    private function buildPrompt(string $question, array $context = []): string
    {
        $weather = $this->buildWeatherContext($context['weather'] ?? null);
        $menuMatches = $this->buildMenuMatchesContext($context['menu_matches'] ?? []);

        return sprintf(
            "You are BIG 4 Coffee Lounge assistant. Keep replies concise, friendly, and practical. You can answer about: reservations, menu highlights, delivery, and opening hours. When recommending dishes, use the current weather to steer the customer toward suitable food and drinks. If the weather is hot, prefer fresh, chilled, light, or iced items. If it is rainy or cool, prefer warm, comforting dishes and hot drinks. If you do not know a fact, say so clearly and suggest contacting support. %s %s User question: %s",
            $weather,
            $menuMatches,
            $question
        );
    }

    private function normalize(string $text): string
    {
        return mb_substr(trim($text), 0, 800);
    }

    private function fallback(string $question, array $context = []): string
    {
        $q = mb_strtolower($question);
        $weather = is_array($context['weather'] ?? null) ? $context['weather'] : null;
        $weatherLabel = $this->formatWeatherLabel($weather);

        if (str_contains($q, 'reservation') || str_contains($q, 'book')) {
            return 'You can reserve a table from the Book Now button. Choose date, time, guests, and optionally use AI Assign Best Table.';
        }

        if (str_contains($q, 'delivery') || str_contains($q, 'order')) {
            return 'Add items to cart, choose Delivery at checkout, and confirm your address and location details.';
        }

        if (str_contains($q, 'hour') || str_contains($q, 'open')) {
            return 'BIG 4 is open daily from 08:00 to midnight.';
        }

        if (str_contains($q, 'weather') || str_contains($q, 'hot') || str_contains($q, 'rain') || str_contains($q, 'cold')) {
            return $weatherLabel !== null
                ? sprintf('Current weather in %s suggests %s.', $weatherLabel['city'], $weatherLabel['recommendation'])
                : 'I can guide you toward lighter dishes in hot weather and warmer comfort dishes when it is cool or rainy.';
        }

        if (str_contains($q, 'menu') || str_contains($q, 'dish') || str_contains($q, 'coffee') || str_contains($q, 'eat')) {
            if ($weatherLabel !== null) {
                return sprintf(
                    'Right now in %s it is %s at %.1f°C, so I would lean toward %s. You can browse the Main Menu section and add items directly to your cart.',
                    $weatherLabel['city'],
                    $weatherLabel['description'],
                    $weatherLabel['temp'],
                    $weatherLabel['recommendation']
                );
            }

            return 'You can browse available dishes in the Main Menu section and add items directly to your cart.';
        }

        return 'I can help with reservations, delivery, menu browsing, and opening hours. What do you need?';
    }

    private function buildWeatherContext(mixed $weather): string
    {
        if (!is_array($weather) || !isset($weather['city'], $weather['description'], $weather['temp'])) {
            return 'Weather context unavailable.';
        }

        return sprintf(
            'Current weather in %s: %s, %.1f°C.',
            (string) $weather['city'],
            (string) $weather['description'],
            (float) $weather['temp']
        );
    }

    /**
     * @param mixed $menuMatches
     */
    private function buildMenuMatchesContext(mixed $menuMatches): string
    {
        if (!is_array($menuMatches) || $menuMatches === []) {
            return 'No menu shortlist was provided.';
        }

        $items = array_values(array_filter(array_map(static function ($match): ?string {
            if (!is_array($match)) {
                return null;
            }

            $name = trim((string) ($match['name'] ?? ''));
            $menuTitle = trim((string) ($match['menuTitle'] ?? ''));
            if ($name === '') {
                return null;
            }

            return $menuTitle !== '' ? sprintf('%s (%s)', $name, $menuTitle) : $name;
        }, $menuMatches)));

        if ($items === []) {
            return 'No menu shortlist was provided.';
        }

        return 'Current best menu matches: ' . implode(', ', array_slice($items, 0, 5)) . '.';
    }

    /**
     * @param array<string, mixed>|null $weather
     * @return array{city: string, description: string, temp: float, recommendation: string}|null
     */
    private function formatWeatherLabel(?array $weather): ?array
    {
        if (!is_array($weather) || !isset($weather['city'], $weather['description'], $weather['temp'])) {
            return null;
        }

        $recommendation = 'balanced dishes and signature drinks';
        if (($weather['is_hot'] ?? false) === true) {
            $recommendation = 'fresh salads, coolers, iced coffee, and lighter plates';
        } elseif (($weather['is_rainy'] ?? false) === true) {
            $recommendation = 'warm pasta, hearty mains, and hot coffee';
        }

        return [
            'city' => (string) $weather['city'],
            'description' => (string) $weather['description'],
            'temp' => (float) $weather['temp'],
            'recommendation' => $recommendation,
        ];
    }
}
