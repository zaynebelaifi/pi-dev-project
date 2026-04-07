<?php

namespace App\Service;

use App\Entity\Ingredient;
use App\Repository\DishIngredientRepository;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;

class IngredientInventorySyncService
{
    public function __construct(
        private readonly IngredientRepository $ingredientRepository,
        private readonly DishIngredientRepository $dishIngredientRepository,
        private readonly ExpiredIngredientWasteService $expiredIngredientWasteService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{expiredMoved:int, duplicatesRemoved:int, recipeLinesMerged:int}
     */
    public function syncAll(): array
    {
        $expiredMoved = $this->expiredIngredientWasteService->moveExpiredStockToWaste();

        $ingredients = $this->ingredientRepository->findBy([], ['id' => 'ASC']);
        $groups = [];

        foreach ($ingredients as $ingredient) {
            $name = mb_strtolower(trim((string) $ingredient->getName()));
            $unit = mb_strtolower(trim((string) $ingredient->getUnit()));

            if ('' === $name || '' === $unit) {
                continue;
            }

            $key = $name.'|'.$unit;
            $groups[$key][] = $ingredient;
        }

        $duplicatesRemoved = 0;
        $recipeLinesMerged = 0;

        foreach ($groups as $group) {
            if (count($group) < 2) {
                continue;
            }

            usort($group, static fn (Ingredient $a, Ingredient $b): int => ($a->getId() ?? 0) <=> ($b->getId() ?? 0));

            $primary = array_shift($group);
            if (!$primary instanceof Ingredient) {
                continue;
            }

            foreach ($group as $duplicate) {
                $primaryStock = (float) ($primary->getQuantityInStock() ?? 0);
                $duplicateStock = (float) ($duplicate->getQuantityInStock() ?? 0);
                $primary->setQuantityInStock($primaryStock + $duplicateStock);

                $primary->setMinStockLevel(max(
                    (float) ($primary->getMinStockLevel() ?? 0),
                    (float) ($duplicate->getMinStockLevel() ?? 0)
                ));

                $primary->setUnitCost(max(
                    (float) ($primary->getUnitCost() ?? 0),
                    (float) ($duplicate->getUnitCost() ?? 0)
                ));

                $primaryExpiry = $primary->getExpiryDate();
                $duplicateExpiry = $duplicate->getExpiryDate();
                if ($duplicateExpiry instanceof \DateTimeInterface && (!$primaryExpiry instanceof \DateTimeInterface || $duplicateExpiry > $primaryExpiry)) {
                    $primary->setExpiryDate($duplicateExpiry);
                }

                foreach ($duplicate->getDishIngredients()->toArray() as $duplicateLine) {
                    $dish = $duplicateLine->getDish();
                    if (null === $dish) {
                        continue;
                    }

                    $existingLine = $this->dishIngredientRepository->findOneByDishAndIngredient($dish, $primary);
                    if (null !== $existingLine && $existingLine !== $duplicateLine) {
                        $existingLine->setQuantityRequired(
                            (float) ($existingLine->getQuantityRequired() ?? 0) + (float) ($duplicateLine->getQuantityRequired() ?? 0)
                        );
                        $this->entityManager->remove($duplicateLine);
                        $recipeLinesMerged++;
                        continue;
                    }

                    $duplicateLine->setIngredient($primary);
                }

                foreach ($duplicate->getWasteRecords()->toArray() as $wasteRecord) {
                    $wasteRecord->setIngredient($primary);
                }

                $this->entityManager->remove($duplicate);
                $duplicatesRemoved++;
            }
        }

        if ($duplicatesRemoved > 0 || $recipeLinesMerged > 0) {
            $this->entityManager->flush();
        }

        return [
            'expiredMoved' => $expiredMoved,
            'duplicatesRemoved' => $duplicatesRemoved,
            'recipeLinesMerged' => $recipeLinesMerged,
        ];
    }
}
