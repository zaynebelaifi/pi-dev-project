<?php

namespace App\Service;

use App\Entity\Wasterecord;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;

class ExpiredIngredientWasteService
{
    public function __construct(
        private readonly IngredientRepository $ingredientRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function moveExpiredStockToWaste(): int
    {
        $today = new \DateTimeImmutable('today');
        $expiredIngredients = $this->ingredientRepository->findExpiredWithStock($today);

        $movedCount = 0;

        foreach ($expiredIngredients as $ingredient) {
            $stock = (float) ($ingredient->getQuantityInStock() ?? 0);
            if ($stock <= 0) {
                continue;
            }

            $wasteRecord = new Wasterecord();
            $wasteRecord->setIngredient($ingredient);
            $wasteRecord->setQuantityWasted($stock);
            $wasteRecord->setWasteType('Expired');
            $wasteRecord->setReason('Auto-recorded: ingredient expired and removed from stock');
            $wasteRecord->setDate($today);

            $ingredient->setQuantityInStock(0);

            $this->entityManager->persist($wasteRecord);
            $movedCount++;
        }

        if ($movedCount > 0) {
            $this->entityManager->flush();
        }

        return $movedCount;
    }
}
