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

    public function ask(string $question): string
    {
        $question = trim($question);
        if ($question === '') {
            return 'Ask me about menu items, delivery, reservations, or opening hours.';
        }

        try {
            $prompt = $this->buildPrompt($question);
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
                return $this->fallback($question);
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

            return $this->fallback($question);
        }
    }

    private function buildPrompt(string $question): string
    {
        return sprintf(
            "You are BIG 4 Coffee Lounge assistant. Keep replies concise, friendly, and practical. You can answer about: reservations, menu highlights, delivery, and opening hours. If you do not know a fact, say so clearly and suggest contacting support. User question: %s",
            $question
        );
    }

    private function normalize(string $text): string
    {
        return mb_substr(trim($text), 0, 800);
    }

    private function fallback(string $question): string
    {
        $q = mb_strtolower($question);

        if (str_contains($q, 'reservation') || str_contains($q, 'book')) {
            return 'You can reserve a table from the Book Now button. Choose date, time, guests, and optionally use AI Assign Best Table.';
        }

        if (str_contains($q, 'delivery') || str_contains($q, 'order')) {
            return 'Add items to cart, choose Delivery at checkout, and confirm your address and location details.';
        }

        if (str_contains($q, 'hour') || str_contains($q, 'open')) {
            return 'BIG 4 is open daily from 08:00 to midnight.';
        }

        if (str_contains($q, 'menu') || str_contains($q, 'dish') || str_contains($q, 'coffee')) {
            return 'You can browse available dishes in the Main Menu section and add items directly to your cart.';
        }

        return 'I can help with reservations, delivery, menu browsing, and opening hours. What do you need?';
    }
}
