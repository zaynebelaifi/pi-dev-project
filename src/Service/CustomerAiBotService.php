<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CustomerAiBotService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $hfApiUrl,
        private readonly string $hfApiToken,
        private readonly string $hfModel,
        private readonly int $timeout = 20,
    ) {
    }

    public function ask(string $question): string
    {
        $question = trim($question);
        if ($question === '') {
            return 'Ask me about menu items, reservations, delivery, or opening hours.';
        }

        try {
            $prompt = $this->buildPrompt($question);
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];
            if (trim($this->hfApiToken) !== '') {
                $headers['Authorization'] = 'Bearer '.trim($this->hfApiToken);
            }

            $response = $this->httpClient->request('POST', $this->resolveHfUrl(), [
                'headers' => $headers,
                'json' => [
                    'inputs' => $prompt,
                    'parameters' => [
                        'max_new_tokens' => 180,
                        'temperature' => 0.4,
                        'return_full_text' => false,
                    ],
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

            $generated = $this->extractHfText($body);
            if ($generated === '') {
                return $this->fallback($question);
            }

            return $this->normalize($generated);
        } catch (\Throwable $e) {
            $this->logger->warning('Customer AI bot fallback used.', ['message' => $e->getMessage()]);

            return $this->fallback($question);
        }
    }

    private function buildPrompt(string $question): string
    {
        return sprintf(
            "You are BIG 4 Coffee Lounge assistant. Keep replies concise, friendly, and practical. You can answer about menu highlights, reservations, delivery, and opening hours. If the user asks for booking help, ask for date, time, and number of guests and direct them to the reservation process. If you do not know a fact, say so clearly and suggest contacting support. User question: %s",
            $question
        );
    }

    private function resolveHfUrl(): string
    {
        $baseUrl = rtrim($this->hfApiUrl, '/');
        if ($this->hfModel === '') {
            return $baseUrl;
        }

        if (str_contains($baseUrl, '{model}')) {
            return str_replace('{model}', rawurlencode($this->hfModel), $baseUrl);
        }

        if (str_contains($baseUrl, '/models/')) {
            return $baseUrl;
        }

        if (str_ends_with($baseUrl, '/models')) {
            return $baseUrl.'/'.rawurlencode($this->hfModel);
        }

        return $baseUrl.'/models/'.rawurlencode($this->hfModel);
    }

    private function extractHfText(string $body): string
    {
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return $this->normalize($body);
        }

        $text = '';
        if (isset($decoded[0]) && is_array($decoded[0])) {
            $text = (string) ($decoded[0]['generated_text'] ?? $decoded[0]['text'] ?? '');
        } else {
            $text = (string) ($decoded['generated_text'] ?? $decoded['text'] ?? $decoded['response'] ?? $decoded['output'] ?? '');
        }

        return $this->normalize($text);
    }

    private function normalize(string $text): string
    {
        return mb_substr(trim($text), 0, 800);
    }

    private function fallback(string $question): string
    {
        $q = mb_strtolower($question);

        if (str_contains($q, 'table') || str_contains($q, 'reservation') || str_contains($q, 'book')) {
            return 'To book a table, please use the reservation form and provide date (YYYY-MM-DD), time (HH:MM), and number of guests.';
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

        return 'I can help with menu, reservations, delivery, and opening-hours questions.';
    }
}
