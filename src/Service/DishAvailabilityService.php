<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\DishIngredient;
use App\Repository\DishIngredientRepository;

class DishAvailabilityService
{
    public function __construct(private readonly DishIngredientRepository $dishIngredientRepository)
    {
    }

    /**
     * @return array{is_available: bool, possible_servings: int, reasons: string[], has_recipe: bool}
     */
    public function evaluateDish(Dish $dish): array
    {
        $recipeLines = $this->dishIngredientRepository->findByDishWithIngredient($dish);

        return $this->evaluateWithRecipeLines($recipeLines);
    }

    /**
     * @param Dish[] $dishes
     * @return array<int, array{is_available: bool, possible_servings: int, reasons: string[], has_recipe: bool}>
     */
    public function evaluateForDishes(array $dishes): array
    {
        $result = [];

        foreach ($dishes as $dish) {
            if (!$dish instanceof Dish || null === $dish->getId()) {
                continue;
            }

            $result[$dish->getId()] = $this->evaluateDish($dish);
        }

        return $result;
    }

    /**
     * @param DishIngredient[] $recipeLines
     * @return array{is_available: bool, possible_servings: int, reasons: string[], has_recipe: bool}
     */
    private function evaluateWithRecipeLines(array $recipeLines): array
    {
        if ([] === $recipeLines) {
            return [
                'is_available' => false,
                'possible_servings' => 0,
                'reasons' => ['No recipe lines configured.'],
                'has_recipe' => false,
            ];
        }

        $today = new \DateTimeImmutable('today');
        $reasons = [];
        $servings = null;

        foreach ($recipeLines as $line) {
            $ingredient = $line->getIngredient();
            $required = (float) ($line->getQuantityRequired() ?? 0);

            if (null === $ingredient) {
                $reasons[] = 'Recipe has a missing ingredient reference.';
                continue;
            }

            if ($required <= 0) {
                $reasons[] = sprintf('Invalid required quantity for %s.', $ingredient->getName());
                continue;
            }

            $stock = (float) ($ingredient->getQuantityInStock() ?? 0);
            $expiry = $ingredient->getExpiryDate();

            if (null !== $expiry && $expiry < $today) {
                $reasons[] = sprintf('%s is expired.', $ingredient->getName());
                continue;
            }

            if ($stock < $required) {
                $reasons[] = sprintf('Not enough %s in stock.', $ingredient->getName());
                continue;
            }

            $ingredientServings = (int) floor($stock / $required);
            $servings = null === $servings ? $ingredientServings : min($servings, $ingredientServings);
        }

        $possibleServings = max(0, (int) ($servings ?? 0));
        $isAvailable = [] === $reasons && $possibleServings > 0;

        return [
            'is_available' => $isAvailable,
            'possible_servings' => $possibleServings,
            'reasons' => $reasons,
            'has_recipe' => true,
        ];
    }
}
