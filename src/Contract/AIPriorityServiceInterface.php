<?php

namespace App\Contract;

use App\Entity\Delivery;

interface AIPriorityServiceInterface
{
    /**
     * Calculates a priority score for a delivery based on wait time, distance, and VIP status.
     */
    public function calculatePriorityScore(Delivery $delivery): float;

    /**
     * Suggests the best driver and car for a delivery.
     */
    public function suggestBestAssignment(Delivery $delivery): array;
}
