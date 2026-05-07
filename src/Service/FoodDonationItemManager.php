<?php

namespace App\Service;

use App\Entity\FoodDonationItem;
use InvalidArgumentException;

class FoodDonationItemManager
{
    public function validate(FoodDonationItem $item): void
    {
        if ($item->getItemId() === null || $item->getItemId() <= 0) {
            if ($item->getItemId() === null) {
                throw new InvalidArgumentException('Item ID must be assigned');
            }

            throw new InvalidArgumentException('Item ID must be greater than 0');
        }

        if ($item->getQuantity() === null || $item->getQuantity() <= 0) {
            throw new InvalidArgumentException('Item quantity must be greater than 0');
        }

        if ($item->getDonationEventId() === null) {
            throw new InvalidArgumentException('Item must be assigned to a donation event');
        }
    }
}
