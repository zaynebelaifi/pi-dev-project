<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\DishIngredient;
use App\Entity\Ingredient;
use App\Repository\DishIngredientRepository;
use App\Repository\DishRepository;
use App\Repository\IngredientRepository;

class DonationOptimizationService
{
    public function __construct(
        private readonly IngredientRepository $ingredientRepository,
        private readonly DishRepository $dishRepository,
        private readonly DishIngredientRepository $dishIngredientRepository,
    ) {
    }

    /**
     * @return array<int, array{dish: Dish, maxDishCount: int, nearExpiryUsageScore: float, costSavingScore: float, totalPriorityScore: float, ingredientUsages: array<int, array{ingredient: Ingredient, quantityRequiredPerDish: float, nearExpiryStockQuantity: float, nearExpiryQuantityConsumed: float}>}>
     */
    public function getRecommendations(int $nearExpiryDays, float $costSavingWeight = 0.2): array
    {
        if ($nearExpiryDays < 0) {
            throw new \InvalidArgumentException('nearExpiryDays must be >= 0');
        }

        $today = new \DateTimeImmutable('today');
        $upperBound = $today->modify('+'.$nearExpiryDays.' days');

        $nearExpiryIngredients = $this->ingredientRepository->createQueryBuilder('i')
            ->andWhere('i.expiryDate IS NOT NULL')
            ->andWhere('i.quantityInStock > 0')
            ->andWhere('i.expiryDate >= :today')
            ->andWhere('i.expiryDate <= :upper')
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('upper', $upperBound->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        if (!$nearExpiryIngredients) {
            return [];
        }

        $ingredientMap = [];
        $ingredientIds = [];
        foreach ($nearExpiryIngredients as $ingredient) {
            if ($ingredient instanceof Ingredient && null !== $ingredient->getId()) {
                $ingredientMap[$ingredient->getId()] = $ingredient;
                $ingredientIds[] = $ingredient->getId();
            }
        }

        if (!$ingredientIds) {
            return [];
        }

        $lines = $this->dishIngredientRepository->createQueryBuilder('di')
            ->addSelect('i', 'd')
            ->innerJoin('di.ingredient', 'i')
            ->innerJoin('di.dish', 'd')
            ->andWhere('i.id IN (:ids)')
            ->setParameter('ids', $ingredientIds)
            ->getQuery()
            ->getResult();

        if (!$lines) {
            return [];
        }

        $availableDishes = [];
        foreach ($this->dishRepository->findAll() as $dish) {
            if ($dish instanceof Dish && $dish->isAvailable()) {
                $availableDishes[$dish->getId()] = $dish;
            }
        }

        $grouped = [];
        foreach ($lines as $line) {
            if (!$line instanceof DishIngredient) {
                continue;
            }
            $dish = $line->getDish();
            if (!$dish || null === $dish->getId() || !isset($availableDishes[$dish->getId()])) {
                continue;
            }
            $grouped[$dish->getId()][] = $line;
        }

        $recommendations = [];

        foreach ($grouped as $dishId => $dishLines) {
            $dish = $availableDishes[$dishId] ?? null;
            if (!$dish) {
                continue;
            }

            $maxDishCount = null;
            foreach ($dishLines as $line) {
                $ingredient = $line->getIngredient();
                $required = (float) ($line->getQuantityRequired() ?? 0);
                if (!$ingredient instanceof Ingredient || $required <= 0) {
                    $maxDishCount = 0;
                    break;
                }
                $stock = (float) ($ingredient->getQuantityInStock() ?? 0);
                $possible = (int) floor($stock / $required);
                $maxDishCount = null === $maxDishCount ? $possible : min($maxDishCount, $possible);
            }

            if (!$maxDishCount || $maxDishCount <= 0) {
                continue;
            }

            $nearExpiryUsageScore = 0.0;
            $costSavingScore = 0.0;
            $ingredientUsages = [];

            foreach ($dishLines as $line) {
                $ingredient = $line->getIngredient();
                if (!$ingredient instanceof Ingredient) {
                    continue;
                }
                $required = (float) ($line->getQuantityRequired() ?? 0);
                if ($required <= 0) {
                    continue;
                }
                $stock = (float) ($ingredient->getQuantityInStock() ?? 0);
                $consumed = $maxDishCount * $required;

                $nearExpiryUsageScore += $consumed;
                $costSavingScore += $consumed * (float) ($ingredient->getUnitCost() ?? 0);

                $ingredientUsages[] = [
                    'ingredient' => $ingredient,
                    'quantityRequiredPerDish' => $required,
                    'nearExpiryStockQuantity' => $stock,
                    'nearExpiryQuantityConsumed' => $consumed,
                ];
            }

            $recommendations[] = [
                'dish' => $dish,
                'maxDishCount' => $maxDishCount,
                'nearExpiryUsageScore' => $nearExpiryUsageScore,
                'costSavingScore' => $costSavingScore,
                'totalPriorityScore' => $nearExpiryUsageScore + ($costSavingScore * $costSavingWeight),
                'ingredientUsages' => $ingredientUsages,
            ];
        }

        usort($recommendations, static function (array $left, array $right): int {
            return ($right['totalPriorityScore'] <=> $left['totalPriorityScore'])
                ?: ($right['nearExpiryUsageScore'] <=> $left['nearExpiryUsageScore'])
                ?: ($right['costSavingScore'] <=> $left['costSavingScore']);
        });

        return $recommendations;
    }
}
