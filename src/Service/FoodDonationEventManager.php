<?php

namespace App\Service;

use App\Entity\FoodDonationEvent;
use InvalidArgumentException;
use DateTime;

class FoodDonationEventManager
{
    public function validate(FoodDonationEvent $event): void
    {
        // Rule 1: Check if charityName is not empty and at least 3 chars
        if (empty($event->getCharityName()) || strlen((string)$event->getCharityName()) < 3) {
            throw new InvalidArgumentException('Charity name must be at least 3 characters');
        }

        // Rule 2: Check if totalQuantity is positive
        if ($event->getTotalQuantity() <= 0) {
            throw new InvalidArgumentException('Total quantity must be greater than 0');
        }

        // Rule 3: Check if eventDate is in the future
        $now = new DateTime();
        if ($event->getEventDate() === null || $event->getEventDate() <= $now) {
            throw new InvalidArgumentException('Event date must be in the future');
        }

        // Rule 4: Check if status is valid
        $validStatuses = ['SCHEDULED', 'COMPLETED', 'CANCELLED'];
        if (!in_array($event->getStatus(), $validStatuses, true)) {
            throw new InvalidArgumentException('Invalid status. Must be one of: ' . implode(', ', $validStatuses));
        }
    }
}