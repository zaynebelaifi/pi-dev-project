<?php

namespace App\Utils;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiStockInsightService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function answerQuestion(string $question, array $context): array
    {
        $normalizedQuestion = trim($question);
        if ('' === $normalizedQuestion) {
            return [
                'answer' => 'Ask a stock question, for example: Which ingredient is most likely to stock out this week?',
                'usedFallback' => true,
            ];
        }

        $apiKey = $this->resolveApiKey();
        if ('' === $apiKey) {
            return [
                'answer' => $this->buildLocalFallbackAnswer($normalizedQuestion, $context),
                'usedFallback' => true,
                'reason' => 'Missing AI_STOCK_API_KEY or OPENAI_API_KEY',
            ];
        }

        try {
            $response = $this->httpClient->request('POST', $this->getEnv('AI_STOCK_API_URL', 'https://api.openai.com/v1/chat/completions'), [
                'headers' => [
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->getEnv('AI_STOCK_MODEL', 'gpt-4o-mini'),
                    'temperature' => 0.2,
                    'max_tokens' => 380,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a concise restaurant stock analyst. Use only provided context. Give action-oriented recommendations with numbers when available.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->buildPrompt($normalizedQuestion, $context),
                        ],
                    ],
                ],
                'timeout' => 20,
            ]);

            $payload = $response->toArray(false);
            $answer = trim($this->extractAssistantText($payload));
            if ('' === $answer) {
                $answer = $this->buildLocalFallbackAnswer($normalizedQuestion, $context);

                return [
                    'answer' => $answer,
                    'usedFallback' => true,
                    'reason' => 'Empty model response',
                ];
            }

            return [
                'answer' => $answer,
                'usedFallback' => false,
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('AI stock insight API failed, using fallback answer.', [
                'message' => $e->getMessage(),
            ]);

            return [
                'answer' => $this->buildLocalFallbackAnswer($normalizedQuestion, $context),
                'usedFallback' => true,
                'reason' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function buildPrompt(string $question, array $context): string
    {
        $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return "Question:\n{$question}\n\nLive stock context:\n{$contextJson}\n\nRespond in plain language for an admin user. Keep under 180 words.";
    }

    /**
     * @param array<string, mixed> $context
     */
    private function buildLocalFallbackAnswer(string $question, array $context): string
    {
        $q = mb_strtolower($question);

        $weather = $context['weather'] ?? [];
        $shortages = $context['shortageAlerts'] ?? [];
        $expiries = $context['expiryAlerts'] ?? [];
        $predictedWaste = $context['predictedWaste'] ?? ['quantity' => 0, 'cost' => 0];

        if ($this->containsAny($q, ['stockout', 'stock out', 'shortage', 'shortages', 'out of stock', 'stock risk', 'risk'])) {
            if (!empty($shortages)) {
                $top = array_slice($shortages, 0, 3);
                $lines = array_map(static fn (array $row): string => sprintf('%s (gap %.2f)', $row['ingredient'], (float) $row['shortageGap']), $top);

                return 'Potential stockouts right now: '.implode(', ', $lines).'. Prioritize replenishment for these ingredients first.';
            }

            return 'No immediate stockout alerts were detected. Continue monitoring low-stock items and near-expiry items daily.';
        }

        if ($this->containsAny($q, ['expiry', 'expire', 'near-expiry', 'near expiry'])) {
            if (!empty($expiries)) {
                $top = array_slice($expiries, 0, 3);
                $lines = array_map(static fn (array $row): string => sprintf('%s (%s)', $row['ingredient'], $row['weatherAdjustedExpiryDate']), $top);

                return 'Top expiry alerts: '.implode(', ', $lines).'. Consider menu push, bundle offers, or donation flow for these items.';
            }

            return 'No critical expiry alerts in the immediate window. Keep tracking weather-adjusted expiry dates for accuracy.';
        }

        if ($this->containsAny($q, ['waste', 'spoilage', 'discard'])) {
            return sprintf(
                'Predicted waste in the near window is %.2f units (about %.2f TND). Focus on high-risk ingredients and convert near-expiry stock into specials.',
                (float) ($predictedWaste['quantity'] ?? 0),
                (float) ($predictedWaste['cost'] ?? 0)
            );
        }

        if ($this->containsAny($q, ['weather', 'temperature', 'cool', 'hot', 'heat'])) {
            return sprintf(
                'Current weather status: %s. Demand multiplier is x%.2f and expiry acceleration is x%.2f, so adjust procurement and shelf-life expectations accordingly.',
                (string) ($weather['statusLabel'] ?? 'Unknown'),
                (float) ($weather['demandMultiplier'] ?? 1),
                (float) ($weather['expiryAcceleration'] ?? 1)
            );
        }

        if ($this->containsAny($q, ['cost', 'costs', 'inventory cost', 'fix inventory', 'inventory fix'])) {
            if (!empty($shortages)) {
                $estimated = 0.0;
                foreach ($shortages as $shortage) {
                    $estimated += (float) ($shortage['shortageGap'] ?? 0);
                }

                return sprintf(
                    'Estimated next inventory correction volume is %.2f units across shortage alerts. Prioritize top stockout-risk ingredients first, then reassess after one procurement cycle.',
                    $estimated
                );
            }

            return 'Current shortage alerts are minimal, so immediate inventory correction cost should be low. Review low-stock and near-expiry items before placing urgent orders.';
        }

        return sprintf(
            'Live summary: weather=%s, demand x%.2f, expiry acceleration x%.2f, predicted waste %.2f units. Ask about stockouts, expiry alerts, waste reduction, or weather impact for a focused answer.',
            (string) ($weather['statusLabel'] ?? 'Unknown'),
            (float) ($weather['demandMultiplier'] ?? 1),
            (float) ($weather['expiryAcceleration'] ?? 1),
            (float) ($predictedWaste['quantity'] ?? 0)
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractAssistantText(array $payload): string
    {
        $content = $payload['choices'][0]['message']['content'] ?? null;

        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            $parts = [];
            foreach ($content as $chunk) {
                if (is_array($chunk) && isset($chunk['text']) && is_string($chunk['text'])) {
                    $parts[] = $chunk['text'];
                }
            }

            return implode("\n", $parts);
        }

        return '';
    }

    private function resolveApiKey(): string
    {
        $primary = $this->getEnv('AI_STOCK_API_KEY', '');
        if ('' !== $primary) {
            return $primary;
        }

        return $this->getEnv('OPENAI_API_KEY', '');
    }

    /**
     * @param string[] $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
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
