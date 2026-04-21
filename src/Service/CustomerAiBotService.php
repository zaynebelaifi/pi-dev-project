<?php

namespace App\Service;

use App\Service\SmartTableMatcher;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CustomerAiBotService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly SmartTableMatcher $smartTableMatcher,
        private readonly string $hfApiUrl,
        private readonly string $hfApiToken,
        private readonly string $hfModel,
        private readonly int $timeout = 20,
    ) {
    }

    public function ask(string $question, ?array $bookingContext = null, ?int $clientId = null): string
    {
        $question = trim($question);
        if ($question === '') {
            return 'Ask me about table booking (date, time, guests), menu items, delivery, or opening hours.';
        }

        $tableGuidance = $this->buildTableGuidance($question, $bookingContext, $clientId);

        try {
            $prompt = $this->buildPrompt($question, $tableGuidance);
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
                return $this->fallback($question, $tableGuidance);
            }

            $generated = $this->extractHfText($body);
            if ($generated === '') {
                return $this->fallback($question, $tableGuidance);
            }

            $combined = $generated;
            if ($tableGuidance !== null) {
                $combined = trim($generated.' '.$tableGuidance);
            }

            return $this->normalize($combined);
        } catch (\Throwable $e) {
            $this->logger->warning('Customer AI bot fallback used.', ['message' => $e->getMessage()]);

            return $this->fallback($question, $tableGuidance);
        }
    }

    private function buildPrompt(string $question, ?string $tableGuidance): string
    {
        $tableContext = $tableGuidance !== null && $tableGuidance !== ''
            ? 'Table recommendation context: '.$tableGuidance
            : 'If the user asks about table choice, ask for date (YYYY-MM-DD), time (HH:MM), and guest count.';

        return sprintf(
            "You are BIG 4 Coffee Lounge assistant. Keep replies concise, friendly, and practical. Prioritize helping customers choose the right table based on date, time, number of guests, occasion, and mobility needs. %s You can also answer about: reservations, menu highlights, delivery, and opening hours. If you do not know a fact, say so clearly and suggest contacting support. User question: %s",
            $tableContext,
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

    private function fallback(string $question, ?string $tableGuidance): string
    {
        if ($tableGuidance !== null && $tableGuidance !== '') {
            return $tableGuidance;
        }

        $q = mb_strtolower($question);

        if (str_contains($q, 'table') || str_contains($q, 'reservation') || str_contains($q, 'book')) {
            return 'To suggest the best table, share your booking date (YYYY-MM-DD), time (HH:MM), number of guests, and optional occasion/mobility needs.';
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

        return 'I can help you choose a table, reserve it, or answer menu, delivery, and opening-hours questions.';
    }

    private function buildTableGuidance(string $question, ?array $bookingContext, ?int $clientId): ?string
    {
        $q = mb_strtolower($question);
        $tableIntent = str_contains($q, 'table')
            || str_contains($q, 'seat')
            || str_contains($q, 'reservation')
            || str_contains($q, 'book')
            || str_contains($q, 'choose');

        $dateInput = trim((string) ($bookingContext['date'] ?? ''));
        if ($dateInput === '' && preg_match('/\b(20\d{2}-\d{2}-\d{2})\b/', $question, $dateMatch) === 1) {
            $dateInput = $dateMatch[1];
        }

        $timeInput = trim((string) ($bookingContext['time'] ?? ''));
        if ($timeInput === '' && preg_match('/\b([01]?\d|2[0-3]):([0-5]\d)\b/', $question, $timeMatch) === 1) {
            $timeInput = sprintf('%02d:%02d', (int) $timeMatch[1], (int) $timeMatch[2]);
        }

        $guests = (int) ($bookingContext['guests'] ?? 0);
        if ($guests < 1) {
            if (preg_match('/\b(\d{1,2})\s*(?:people|persons|guests|pax|seats?)\b/i', $question, $guestMatch) === 1) {
                $guests = (int) $guestMatch[1];
            } elseif (preg_match('/\bfor\s+(\d{1,2})\b/i', $question, $forMatch) === 1) {
                $guests = (int) $forMatch[1];
            }
        }

        $occasion = trim((string) ($bookingContext['occasion'] ?? ''));
        if ($occasion === '') {
            foreach (['date', 'romantic', 'anniversary', 'birthday', 'celebration', 'party', 'business', 'meeting'] as $keyword) {
                if (str_contains($q, $keyword)) {
                    $occasion = $keyword;
                    break;
                }
            }
        }

        $mobilityNeeds = (bool) ($bookingContext['mobility_needs'] ?? false);
        if (!$mobilityNeeds) {
            $mobilityNeeds = str_contains($q, 'wheelchair')
                || str_contains($q, 'mobility')
                || str_contains($q, 'accessible');
        }

        if (!$tableIntent && $guests < 1) {
            return null;
        }

        if ($guests < 1 || $dateInput === '' || $timeInput === '') {
            return 'I can choose a table for you. Please share date (YYYY-MM-DD), time (HH:MM), guests, and any occasion or mobility needs.';
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateInput);
        $time = \DateTimeImmutable::createFromFormat('!H:i', $timeInput);
        if (!$date || !$time) {
            return 'Please use date format YYYY-MM-DD and time format HH:MM so I can recommend the best table.';
        }

        $recommendations = $this->smartTableMatcher->recommendRanked(
            date: $date,
            time: $time,
            guests: $guests,
            occasion: $occasion,
            mobilityNeeds: $mobilityNeeds,
            clientId: $clientId !== null && $clientId > 0 ? $clientId : null,
            limit: 3,
        );

        if ($recommendations === []) {
            return sprintf(
                'No suitable table is free for %d guest(s) on %s at %s. Please try another time or party size.',
                $guests,
                $dateInput,
                $timeInput
            );
        }

        $best = $recommendations[0];
        $alternatives = [];
        foreach (array_slice($recommendations, 1, 2) as $candidate) {
            $candidateTable = $candidate['table'];
            $alternatives[] = sprintf(
                '#%d (%d seats, %s confidence)',
                $candidateTable->getTableId(),
                $candidateTable->getCapacity(),
                $candidate['confidence'] ?? 'Medium'
            );
        }

        $bestTable = $best['table'];
        $reason = trim((string) ($best['explanation'] ?? implode(' ', array_slice($best['reasons'] ?? [], 0, 2))));
        $message = sprintf(
            'Best match: Table #%d (%d seats, %s confidence) for %d guest(s) on %s at %s.',
            $bestTable->getTableId(),
            $bestTable->getCapacity(),
            $best['confidence'] ?? 'Medium',
            $guests,
            $dateInput,
            $timeInput
        );

        if ($reason !== '') {
            $message .= ' '.$reason;
        }

        if ($alternatives !== []) {
            $message .= ' Alternatives: '.implode('; ', $alternatives).'.';
        }

        return $message;
    }
}
