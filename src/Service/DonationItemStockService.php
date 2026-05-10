<?php

namespace App\Service;

use App\Entity\DishIngredient;
use App\Repository\DishIngredientRepository;
use App\Repository\DishRepository;

class DonationItemStockService
{
    public function __construct(
        private readonly DishRepository $dishRepository,
        private readonly DishIngredientRepository $dishIngredientRepository,
    ) {
    }

    public function consumeForDish(int $dishId, int $portionCount): void
    {
        if ($portionCount <= 0) {
            return;
        }

        $dish = $this->dishRepository->find($dishId);
        if (!$dish) {
            throw new \RuntimeException('Dish not found for donation item.');
        }

        $recipeLines = $this->dishIngredientRepository->findBy(['dish' => $dish]);
        if (!$recipeLines) {
            return;
        }

        foreach ($recipeLines as $line) {
            if (!$line instanceof DishIngredient) {
                continue;
            }
            $ingredient = $line->getIngredient();
            if (!$ingredient) {
                continue;
            }

            $requiredPerDish = (float) ($line->getQuantityRequired() ?? 0);
            $requiredTotal = $requiredPerDish * $portionCount;
            if ($requiredTotal <= 0) {
                continue;
            }

            $stock = (float) ($ingredient->getQuantityInStock() ?? 0);
            if ($stock < $requiredTotal) {
                throw new \RuntimeException(sprintf(
                    'Not enough stock for %s. Required %.2f, available %.2f.',
                    $ingredient->getName() ?? 'ingredient',
                    $requiredTotal,
                    $stock
                ));
            }
        }

        foreach ($recipeLines as $line) {
            if (!$line instanceof DishIngredient) {
                continue;
            }
            $ingredient = $line->getIngredient();
            if (!$ingredient) {
                continue;
            }
            $requiredTotal = (float) ($line->getQuantityRequired() ?? 0) * $portionCount;
            if ($requiredTotal <= 0) {
                continue;
            }
            $stock = (float) ($ingredient->getQuantityInStock() ?? 0);
            $ingredient->setQuantityInStock(max(0, $stock - $requiredTotal));
        }
    }

    public function restoreForDish(int $dishId, int $portionCount): void
    {
        if ($portionCount <= 0) {
            return;
        }

        $dish = $this->dishRepository->find($dishId);
        if (!$dish) {
            return;
        }

        $recipeLines = $this->dishIngredientRepository->findBy(['dish' => $dish]);
        if (!$recipeLines) {
            return;
        }

        foreach ($recipeLines as $line) {
            if (!$line instanceof DishIngredient) {
                continue;
            }
            $ingredient = $line->getIngredient();
            if (!$ingredient) {
                continue;
            }

            $requiredTotal = (float) ($line->getQuantityRequired() ?? 0) * $portionCount;
            if ($requiredTotal <= 0) {
                continue;
            }

            $stock = (float) ($ingredient->getQuantityInStock() ?? 0);
            $ingredient->setQuantityInStock($stock + $requiredTotal);
        }
    }
}
