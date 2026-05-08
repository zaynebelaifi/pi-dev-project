<?php

namespace App\Service;

use App\Entity\Delivery;
use InvalidArgumentException;

class DeliveryManager
{
    /**
     * Validate business rules for Delivery entity.
     *
     * @throws InvalidArgumentException
     */
    public function validate(Delivery $delivery): void
    {
        // order_id is guaranteed to be int (non-nullable)
        $orderId = $delivery->getOrder_id();
        if ($orderId <= 0) {
            throw new InvalidArgumentException('order_id must be a positive integer.');
        }

        // delivery_address is guaranteed to be string (non-nullable)
        $addr = $delivery->getDelivery_address();
        if (mb_strlen(trim($addr)) < 5) {
            throw new InvalidArgumentException('delivery_address must be at least 5 characters.');
        }

        // estimated_time if set must be positive integer
        $est = $delivery->getEstimated_time();
        if ($est !== null && $est <= 0) {
            throw new InvalidArgumentException('estimated_time must be a positive integer when provided.');
        }

        // order_total if set must be numeric and >= 0
        $total = $delivery->getOrder_total();
        if ($total !== null && !is_numeric($total)) {
            throw new InvalidArgumentException('order_total must be a numeric value.');
        }
        if ($total !== null && (float)$total < 0.0) {
            throw new InvalidArgumentException('order_total cannot be negative.');
        }

        // rating if set must be between 1 and 5
        $rating = $delivery->getRating();
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            throw new InvalidArgumentException('rating must be an integer between 1 and 5.');
        }

        // recipient_phone format when provided
        $phone = $delivery->getRecipient_phone();
        if ($phone !== null && $phone !== '' && !preg_match('/^[\+]?[0-9\-\(\)\s]+$/', (string)$phone)) {
            throw new InvalidArgumentException('recipient_phone has invalid format.');
        }

        // scheduled_date cannot be in the past if provided
        $scheduled = $delivery->getScheduled_date();
        if ($scheduled !== null) {
            $now = new \DateTimeImmutable();
            if ($scheduled < $now) {
                throw new InvalidArgumentException('scheduled_date cannot be in the past.');
            }
        }
    }
}
