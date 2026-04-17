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
                'answer' => "Quick help\n- Ask about shortages, near-expiry stock, weather impact, or waste prevention.\n\nExample\n- Which ingredient is most likely to stock out this week?",
                'usedFallback' => true,
            ];
        }

        try {
            $answer = trim($this->askFreeChatApi($normalizedQuestion, $context));
            if ('' === $answer) {
                $answer = $this->buildLocalFallbackAnswer($normalizedQuestion, $context);

                return [
                    'answer' => $answer,
                    'usedFallback' => true,
                    'reason' => 'Empty free API response',
                ];
            }

            $answer = $this->normalizeForReadability($answer);

            return [
                'answer' => $answer,
                'usedFallback' => false,
                'provider' => 'free-chat-api',
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('Free stock AI API failed, using fallback answer.', [
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
    private function askFreeChatApi(string $question, array $context): string
    {
        $endpoint = rtrim($this->getEnv('FREE_CHAT_API_URL', 'https://text.pollinations.ai'), '/');
        $prompt = $this->buildFreePrompt($question, $context);
        $url = $endpoint.'/'.rawurlencode($prompt);
        $timeout = max(2, min(20, (int) $this->getEnv('FREE_CHAT_TIMEOUT', '12')));

        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'Accept' => 'text/plain, application/json',
            ],
            'timeout' => $timeout,
            'max_duration' => $timeout,
        ]);

        $statusCode = $response->getStatusCode();
        $rawBody = trim($response->getContent(false));

        if (200 !== $statusCode) {
            throw new \RuntimeException(sprintf('Free API error %d: %s', $statusCode, mb_substr($rawBody, 0, 180)));
        }

        if ('' === $rawBody) {
            return '';
        }

        if (str_starts_with($rawBody, '{') || str_starts_with($rawBody, '[')) {
            $decoded = $this->decodeJson($rawBody);
            $jsonText = $decoded['text'] ?? $decoded['response'] ?? $decoded['output'] ?? null;
            if (is_string($jsonText) && '' !== trim($jsonText)) {
                return trim($jsonText);
            }
        }

        return mb_substr($rawBody, 0, 1600);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function buildFreePrompt(string $question, array $context): string
    {
        $insights = $context['liveStockInsights'] ?? [];
        $weather = $context['weather'] ?? [];
        $predictedWaste = $context['predictedWaste'] ?? ['quantity' => 0, 'cost' => 0];
        $inventoryItems = $context['inventoryItems'] ?? [];
        $inventoryDigest = $this->buildInventoryDigest($inventoryItems);

        return sprintf(
            'You are a restaurant stock assistant for non-technical admins. Keep the answer short and clear. Use only provided inventory rows for item names, quantities, and prices. Do not invent missing rows. Max 180 words.\n\nReturn plain text in this structure:\nSummary\n- one short bullet\n\nKey numbers\n- up to three bullets\n\nRecommended actions\n1. one action\n\nIf user asks for payment to fix stock, include:\nNext payment: <amount> TND\nItems to order (ingredient, qty to order, unit price, line cost).\n\nFor responses with many item rows (6+), use a markdown table:\n| Ingredient | Qty | Unit Price (TND) | Line Total (TND) |\n|---|---:|---:|---:|\n| ... |\n\nInventory snapshot: total=%d, low=%d, out=%d, nearExpiry=%d, expired=%d, value=%.2f TND. Weather=%s, demand x%.2f, expiry x%.2f. Predicted waste=%.2f units (%.2f TND).\nInventory rows:\n%s\nUser question: %s',
            (int) ($insights['totalIngredients'] ?? 0),
            (int) ($insights['lowStock'] ?? 0),
            (int) ($insights['outOfStock'] ?? 0),
            (int) ($insights['nearExpiry'] ?? 0),
            (int) ($insights['expiredItems'] ?? 0),
            (float) ($insights['inventoryValue'] ?? 0),
            (string) ($weather['statusLabel'] ?? 'Unknown'),
            (float) ($weather['demandMultiplier'] ?? 1),
            (float) ($weather['expiryAcceleration'] ?? 1),
            (float) ($predictedWaste['quantity'] ?? 0),
            (float) ($predictedWaste['cost'] ?? 0),
            $inventoryDigest,
            $question
        );
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
        $insights = $context['liveStockInsights'] ?? [];
        $inventoryItems = is_array($context['inventoryItems'] ?? null) ? $context['inventoryItems'] : [];

        if (!empty($inventoryItems) && $this->containsAny($q, ['exact list', 'item list', 'items', 'quantity', 'quantit', 'price', 'pay', 'payment', 'repair stock', 'what are these items'])) {
            if ($this->containsAny($q, ['pay', 'payment', 'repair stock', 'stock repair', 'reorder', 'order'])) {
                return $this->buildStockRepairPaymentAnswer($inventoryItems);
            }

            return $this->buildInventoryItemsAnswer($inventoryItems);
        }

        if ($this->containsAny($q, ['inventory', 'stock health', 'overall stock', 'how is my inventory', 'inventory status'])) {
            $total = (int) ($insights['totalIngredients'] ?? 0);
            $low = (int) ($insights['lowStock'] ?? 0);
            $out = (int) ($insights['outOfStock'] ?? 0);
            $near = (int) ($insights['nearExpiry'] ?? 0);
            $expired = (int) ($insights['expiredItems'] ?? 0);
            $value = (float) ($insights['inventoryValue'] ?? 0);
            $rate = (float) ($insights['lowStockRate'] ?? 0);

            $risk = 'stable';
            if ($out > 0 || $expired > 0 || $rate >= 20) {
                $risk = 'high-risk';
            } elseif ($low > 0 || $near > 0 || $rate >= 10) {
                $risk = 'watchlist';
            }

            return $this->responseBlock(
                'Inventory status',
                [
                    sprintf('Total ingredients: %d', $total),
                    sprintf('Low stock: %d, Out of stock: %d, Near expiry: %d, Expired: %d', $low, $out, $near, $expired),
                    sprintf('Inventory value: %.2f TND, Low-stock rate: %.1f%% (%s)', $value, $rate, $risk),
                ],
                [
                    'Refill out-of-stock ingredients first.',
                    'Then replenish low-stock items with the biggest shortage gap.',
                ]
            );
        }

        if ($this->containsAny($q, ['stockout', 'stock out', 'shortage', 'shortages', 'out of stock', 'stock risk', 'risk'])) {
            if (!empty($shortages)) {
                $top = array_slice($shortages, 0, 3);
                $lines = array_map(static fn (array $row): string => sprintf('%s (gap %.2f)', $row['ingredient'], (float) $row['shortageGap']), $top);

                return $this->responseBlock(
                    'Shortage risk',
                    [
                        'Top shortage alerts: '.implode(', ', $lines),
                    ],
                    [
                        'Purchase these ingredients first to avoid service disruption.',
                        'Recheck stock after the next delivery cycle.',
                    ]
                );
            }

            return $this->responseBlock(
                'Shortage risk',
                [
                    'No immediate stockout alerts detected.',
                ],
                [
                    'Monitor low-stock and near-expiry items daily.',
                ]
            );
        }

        if ($this->containsAny($q, ['expiry', 'expire', 'near-expiry', 'near expiry'])) {
            if (!empty($expiries)) {
                $top = array_slice($expiries, 0, 3);
                $lines = array_map(static fn (array $row): string => sprintf('%s (%s)', $row['ingredient'], $row['weatherAdjustedExpiryDate']), $top);

                return $this->responseBlock(
                    'Near-expiry alerts',
                    [
                        'Priority items: '.implode(', ', $lines),
                    ],
                    [
                        'Create same-day specials for these items.',
                        'Use bundles, donation, or transfer to reduce waste.',
                    ]
                );
            }

            return $this->responseBlock(
                'Near-expiry alerts',
                [
                    'No critical near-expiry alerts right now.',
                ],
                [
                    'Keep tracking weather-adjusted expiry dates.',
                ]
            );
        }

        if ($this->containsAny($q, ['waste', 'spoilage', 'discard'])) {
            return $this->responseBlock(
                'Waste forecast',
                [
                    sprintf('Predicted waste: %.2f units (%.2f TND)', (float) ($predictedWaste['quantity'] ?? 0), (float) ($predictedWaste['cost'] ?? 0)),
                ],
                [
                    'Move high-risk ingredients into promotions quickly.',
                    'Use FIFO checks at opening and closing shifts.',
                ]
            );
        }

        if ($this->containsAny($q, ['weather', 'temperature', 'cool', 'hot', 'heat'])) {
            return $this->responseBlock(
                'Weather impact',
                [
                    sprintf('Current weather: %s', (string) ($weather['statusLabel'] ?? 'Unknown')),
                    sprintf('Demand multiplier: x%.2f, Expiry multiplier: x%.2f', (float) ($weather['demandMultiplier'] ?? 1), (float) ($weather['expiryAcceleration'] ?? 1)),
                ],
                [
                    'Adjust purchase quantities using the demand multiplier.',
                    'Tighten expiry checks when expiry multiplier increases risk.',
                ]
            );
        }

        if ($this->containsAny($q, ['cost', 'costs', 'inventory cost', 'fix inventory', 'inventory fix'])) {
            if (!empty($shortages)) {
                $estimated = 0.0;
                foreach ($shortages as $shortage) {
                    $estimated += (float) ($shortage['shortageGap'] ?? 0);
                }

                return $this->responseBlock(
                    'Stock repair payment estimate',
                    [
                        sprintf('Estimated correction volume: %.2f units', $estimated),
                        'This is a volume estimate from shortage gaps, not a final invoice.',
                    ],
                    [
                        'Buy top stockout-risk ingredients first.',
                        'Recompute cost using each ingredient unit price for an exact payment total.',
                    ]
                );
            }

            return $this->responseBlock(
                'Stock repair payment estimate',
                [
                    'Current shortage alerts are minimal.',
                    'Immediate correction cost should be low.',
                ],
                [
                    'Review low-stock and near-expiry items before urgent orders.',
                ]
            );
        }

        return $this->responseBlock(
            'Live stock overview',
            [
                sprintf('Weather: %s', (string) ($weather['statusLabel'] ?? 'Unknown')),
                sprintf('Demand x%.2f, Expiry x%.2f', (float) ($weather['demandMultiplier'] ?? 1), (float) ($weather['expiryAcceleration'] ?? 1)),
                sprintf('Predicted waste: %.2f units', (float) ($predictedWaste['quantity'] ?? 0)),
            ],
            [
                'Ask about shortages, near-expiry stock, weather impact, or waste prevention actions.',
            ]
        );
    }

    private function normalizeForReadability(string $answer): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $answer));
        if ('' === $text) {
            return '';
        }

        if (str_contains($text, "\n") || str_contains(mb_strtolower($text), 'summary')) {
            return $text;
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $text) ?: [$text];
        $sentences = array_values(array_filter(array_map(static fn (string $s): string => trim($s), $sentences), static fn (string $s): bool => '' !== $s));

        if (count($sentences) <= 1) {
            return "Summary\n- {$text}";
        }

        $first = array_slice($sentences, 0, 3);
        $rest = array_slice($sentences, 3);
        $out = "Summary\n- ".implode("\n- ", $first);

        if (!empty($rest)) {
            $out .= "\n\nRecommended actions\n1. ".implode("\n1. ", $rest);
        }

        return $out;
    }

    /**
     * @param string[] $keyPoints
     * @param string[] $actions
     */
    private function responseBlock(string $title, array $keyPoints, array $actions = []): string
    {
        $lines = [$title, 'Key points'];
        $shortKeyPoints = array_slice($keyPoints, 0, 2);
        foreach ($shortKeyPoints as $point) {
            $lines[] = '- '.$point;
        }

        if (!empty($actions)) {
            $lines[] = '';
            $lines[] = 'Recommended actions';
            foreach (array_slice($actions, 0, 1) as $index => $action) {
                $lines[] = sprintf('%d. %s', $index + 1, $action);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<int, array<string, mixed>> $inventoryItems
     */
    private function buildInventoryDigest(array $inventoryItems): string
    {
        if (empty($inventoryItems)) {
            return '- No inventory item rows provided.';
        }

        $rows = array_slice($inventoryItems, 0, 60);
        $lines = [];
        foreach ($rows as $item) {
            $lines[] = sprintf(
                '- %s | qty %.2f %s | unit %.2f | status %s | shortage %.2f',
                (string) ($item['ingredient'] ?? 'Unknown'),
                (float) ($item['quantity'] ?? 0),
                (string) ($item['unit'] ?? 'units'),
                (float) ($item['unitCost'] ?? 0),
                (string) ($item['status'] ?? 'unknown'),
                (float) ($item['shortageGap'] ?? 0)
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<int, array<string, mixed>> $inventoryItems
     */
    private function buildInventoryItemsAnswer(array $inventoryItems): string
    {
        $rows = [];
        $totalValue = 0.0;
        foreach ($inventoryItems as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $unitCost = (float) ($item['unitCost'] ?? 0);
            $lineTotal = round($qty * $unitCost, 2);
            $totalValue += $lineTotal;

            $rows[] = sprintf(
                '| %s | %.2f %s | %.2f | %.2f |',
                (string) ($item['ingredient'] ?? 'Unknown'),
                $qty,
                (string) ($item['unit'] ?? 'units'),
                $unitCost,
                $lineTotal
            );
        }

        return implode("\n", [
            'Summary',
            sprintf('- Inventory list with item, quantity, and price (%d items).', count($inventoryItems)),
            '',
            'Key numbers',
            sprintf('- Total stock value: %.2f TND', round($totalValue, 2)),
            '',
            'Items',
            '| Ingredient | Qty | Unit Price (TND) | Line Total (TND) |',
            '|---|---:|---:|---:|',
            ...$rows,
            '',
            'Recommended actions',
            '1. Review out-of-stock and low-stock rows first for urgent restock.',
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $inventoryItems
     */
    private function buildStockRepairPaymentAnswer(array $inventoryItems): string
    {
        $orderRows = [];
        $totalCost = 0.0;
        $totalQty = 0.0;

        foreach ($inventoryItems as $item) {
            $shortageGap = (float) ($item['shortageGap'] ?? 0);
            if ($shortageGap <= 0) {
                continue;
            }

            $unitCost = (float) ($item['unitCost'] ?? 0);
            $lineCost = round($shortageGap * $unitCost, 2);
            $totalQty += $shortageGap;
            $totalCost += $lineCost;

            $orderRows[] = sprintf(
                '| %s | %.2f %s | %.2f | %.2f |',
                (string) ($item['ingredient'] ?? 'Unknown'),
                $shortageGap,
                (string) ($item['unit'] ?? 'units'),
                $unitCost,
                $lineCost
            );
        }

        if (empty($orderRows)) {
            return implode("\n", [
                'Summary',
                '- No shortage-gap purchases are needed right now.',
                '',
                'Recommended actions',
                '1. Keep monitoring low-stock and near-expiry items daily.',
            ]);
        }

        return implode("\n", [
            'Summary',
            sprintf('- Next payment to fix stock: %.2f TND.', round($totalCost, 2)),
            '',
            'Key numbers',
            sprintf('- Next payment: %.2f TND', round($totalCost, 2)),
            sprintf('- Items to order: %d', count($orderRows)),
            sprintf('- Total reorder quantity: %.2f units', round($totalQty, 2)),
            '',
            'Items to order',
            '| Ingredient | Qty to Order | Unit Price (TND) | Line Cost (TND) |',
            '|---|---:|---:|---:|',
            ...$orderRows,
            '',
            'Recommended actions',
            '1. Place this order first, then recheck shortages after delivery.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $rawBody): array
    {
        if ('' === trim($rawBody)) {
            return [];
        }

        try {
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
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
