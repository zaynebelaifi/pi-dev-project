<?php

namespace App\Service;

use App\Entity\Dish;

final class MenuRecommendationService
{
    /**
     * @param array{budget?: string, category?: string, diet?: string, mood?: string, q?: string} $filters
     * @param iterable<Dish> $dishes
     *
     * @return array{recommendedIds: int[], message: string, active: bool}
     */
    public function summarize(array $filters, iterable $dishes): array
    {
        $scores = [];
        foreach ($dishes as $dish) {
            if (!$dish instanceof Dish || null === $dish->getId()) {
                continue;
            }

            $score = $this->score($dish, $filters);
            if ($score > 0) {
                $scores[$dish->getId()] = $score;
            }
        }

        arsort($scores);

        $active = $this->hasActiveFilters($filters);
        $recommendedIds = array_slice(array_keys($scores), 0, 3);

        return [
            'recommendedIds' => array_map('intval', $recommendedIds),
            'message' => $this->buildMessage($filters, count($recommendedIds), $active),
            'active' => $active,
        ];
    }

    /**
     * @param array{budget?: string, category?: string, diet?: string, mood?: string, q?: string} $filters
     */
    public function score(Dish $dish, array $filters): int
    {
        $text = mb_strtolower(trim(sprintf(
            '%s %s %s',
            (string) $dish->getName(),
            (string) $dish->getDescription(),
            $dish->getMenu()?->getTitle() ?? ''
        )));
        $price = $dish->getBasePrice() ?? 0.0;
        $score = 0;

        $budget = (string) ($filters['budget'] ?? '');
        if (($budget === 'low' && $price <= 12.0)
            || ($budget === 'medium' && $price > 12.0 && $price <= 25.0)
            || ($budget === 'premium' && $price >= 25.0)
        ) {
            $score += 3;
        }

        $category = mb_strtolower(trim((string) ($filters['category'] ?? '')));
        if ($category !== '' && str_contains($text, $category)) {
            $score += 3;
        }

        $diet = (string) ($filters['diet'] ?? '');
        $looksMeaty = $this->containsAny($text, ['chicken', 'beef', 'fish', 'meat', 'steak', 'burger']);
        if (($diet === 'vegetarian' && !$looksMeaty) || ($diet === 'non_vegetarian' && $looksMeaty)) {
            $score += 3;
        }

        $mood = (string) ($filters['mood'] ?? '');
        $moodKeywords = [
            'spicy' => ['spicy', 'pepper', 'hot', 'chili'],
            'sweet' => ['sweet', 'cake', 'dessert', 'chocolate', 'pancake'],
            'healthy' => ['salad', 'fresh', 'healthy', 'fruit', 'light'],
            'popular' => ['signature', 'special', 'popular', 'chef', 'big 4'],
        ];

        if (isset($moodKeywords[$mood]) && $this->containsAny($text, $moodKeywords[$mood])) {
            $score += 4;
        }

        $search = mb_strtolower(trim((string) ($filters['q'] ?? '')));
        if ($search !== '' && str_contains($text, $search)) {
            $score += 2;
        }

        return $score;
    }

    /**
     * @param array{budget?: string, category?: string, diet?: string, mood?: string, q?: string} $filters
     */
    public function hasActiveFilters(array $filters): bool
    {
        foreach (['budget', 'category', 'diet', 'mood', 'q'] as $key) {
            if (trim((string) ($filters[$key] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    private function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{budget?: string, category?: string, diet?: string, mood?: string, q?: string} $filters
     */
    private function buildMessage(array $filters, int $count, bool $active): string
    {
        if (!$active) {
            return 'Tell us what you like and we will highlight smart picks from the menu.';
        }

        if ($count > 0) {
            return sprintf('Found %d smart pick%s for your current taste profile.', $count, $count === 1 ? '' : 's');
        }

        return 'No exact smart picks on this page yet. Try a softer budget or another flavor mood.';
    }
}
