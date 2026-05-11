<?php

namespace App\Utils;

final class DishImageCatalog
{
    private const FALLBACK_IMAGE = 'https://placehold.co/900x600/f3eadb/6b4b28?text=BIG+4+Dish';

    private const LEGACY_GENERIC_IMAGES = [
        'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1521017432531-fbd92d768814?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=900&q=80',
    ];

    /**
     * @var array<string, string>
     */
    private const KEYWORD_IMAGE_MAP = [
        'french toast' => 'https://images.unsplash.com/photo-1484723091739-30a097e8f929?auto=format&fit=crop&w=900&q=80',
        'croissant' => 'https://images.unsplash.com/photo-1555507036-ab794f4afe5a?auto=format&fit=crop&w=900&q=80',
        'avocado' => 'https://images.unsplash.com/photo-1541519227354-08fa5d50c44d?auto=format&fit=crop&w=900&q=80',
        'breakfast' => 'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?auto=format&fit=crop&w=900&q=80',
        'cappuccino' => 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&w=900&q=80',
        'latte' => 'https://images.unsplash.com/photo-1561047029-3000c68339ca?auto=format&fit=crop&w=900&q=80',
        'americano' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=900&q=80',
        'frappe' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=900&q=80',
        'matcha' => 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=900&q=80',
        'mocha' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=900&q=80',
        'salad' => 'https://images.unsplash.com/photo-1546793665-c74683f339c1?auto=format&fit=crop&w=900&q=80',
        'caesar' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=900&q=80',
        'burrata' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80',
        'quinoa' => 'https://images.unsplash.com/photo-1543332164-6e82f355badc?auto=format&fit=crop&w=900&q=80',
        'salmon' => 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=900&q=80',
        'burger' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=900&q=80',
        'halloumi' => 'https://images.unsplash.com/photo-1521389508051-d7ffb5dc8f70?auto=format&fit=crop&w=900&q=80',
        'pasta' => 'https://images.unsplash.com/photo-1555949258-eb67b1ef0ceb?auto=format&fit=crop&w=900&q=80',
        'risotto' => 'https://images.unsplash.com/photo-1476124369491-e7addf5db371?auto=format&fit=crop&w=900&q=80',
        'steak' => 'https://images.unsplash.com/photo-1558030006-450675393462?auto=format&fit=crop&w=900&q=80',
        'lamb' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=900&q=80',
        'chicken supreme' => 'https://images.unsplash.com/photo-1604908176997-431f4b991667?auto=format&fit=crop&w=900&q=80',
        'chicken' => 'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=900&q=80',
        'calamari' => 'https://images.unsplash.com/photo-1599487488170-d11ec9c172f0?auto=format&fit=crop&w=900&q=80',
        'fries' => 'https://images.unsplash.com/photo-1576107232684-1279f390859f?auto=format&fit=crop&w=900&q=80',
        'mezze' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=900&q=80',
        'lava cake' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=900&q=80',
        'tiramisu' => 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?auto=format&fit=crop&w=900&q=80',
        'cheesecake' => 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?auto=format&fit=crop&w=900&q=80',
        'brulee' => 'https://images.unsplash.com/photo-1470124182917-cc6e71b22ecc?auto=format&fit=crop&w=900&q=80',
        'mojito' => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&w=900&q=80',
        'sparkler' => 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?auto=format&fit=crop&w=900&q=80',
        'iced tea' => 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=900&q=80',
        'cooler' => 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?auto=format&fit=crop&w=900&q=80',
        'sandwich' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=900&q=80',
        'wrap' => 'https://images.unsplash.com/photo-1539252554453-80ab65ce3586?auto=format&fit=crop&w=900&q=80',
        'panini' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=900&q=80',
        'flatbread' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=900&q=80',
        'danish' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=900&q=80',
        'pain au chocolat' => 'https://images.unsplash.com/photo-1517433670267-08bbd4be890f?auto=format&fit=crop&w=900&q=80',
        'cinnamon roll' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=900&q=80',
        'croissant' => 'https://images.unsplash.com/photo-1555507036-ab794f4afe5a?auto=format&fit=crop&w=900&q=80',
        'coffee' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=900&q=80',
    ];

    public static function resolve(?string $preferredImageUrl, ?string $dishName): string
    {
        $normalizedPreferred = self::normalizeUrl($preferredImageUrl);
        if ($normalizedPreferred !== null && !self::isLegacyGenericImage($normalizedPreferred)) {
            return $normalizedPreferred;
        }

        return self::imageFromDishName($dishName);
    }

    public static function imageFromDishName(?string $dishName): string
    {
        $normalizedName = strtolower(trim((string) $dishName));
        if ($normalizedName === '') {
            return self::FALLBACK_IMAGE;
        }

        foreach (self::KEYWORD_IMAGE_MAP as $keyword => $imageUrl) {
            if (str_contains($normalizedName, $keyword)) {
                return $imageUrl;
            }
        }

        return self::FALLBACK_IMAGE;
    }

    public static function fallbackImage(): string
    {
        return self::FALLBACK_IMAGE;
    }

    private static function normalizeUrl(?string $imageUrl): ?string
    {
        $normalized = trim((string) $imageUrl);
        if ($normalized === '' || strtolower($normalized) === 'apic') {
            return null;
        }

        return filter_var($normalized, FILTER_VALIDATE_URL) ? $normalized : null;
    }

    private static function isLegacyGenericImage(string $imageUrl): bool
    {
        return in_array($imageUrl, self::LEGACY_GENERIC_IMAGES, true);
    }
}
