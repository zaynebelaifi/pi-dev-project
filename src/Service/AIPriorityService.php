<?php
namespace App\Service;

use App\Entity\Delivery;
use App\Repository\DeliveryManRepository;
use Psr\Log\LoggerInterface;

final class AIPriorityService
{
    public function __construct(
        private LogisticsService $logistics,
        private DeliveryManRepository $deliveryManRepo,
        private LoggerInterface $logger
    ) {}

    public function calculatePriorityScore(Delivery $delivery): float
    {
        $score = 0.0;

        // Wait time weight
        $created = $delivery->getCreated_at() ?? $delivery->getCreatedAt();
        if ($created) {
            $waitSeconds = time() - $created->getTimestamp();
            $score += min(1.0, $waitSeconds / 3600.0) * 30.0; // up to 30 pts for long waits
        }

        // Distance weight: find nearest available delivery man
        try {
            $nearest = $this->deliveryManRepo->findNearestAvailable($delivery->getCurrentLatitude(), $delivery->getCurrentLongitude());
            if ($nearest) {
                // use logistics to calculate approximate distance
                $eta = $this->logistics->calculateETA($delivery);
                if (!empty($eta['distance'])) {
                    $km = ((float)$eta['distance']) / 1000.0;
                    $score += max(0, 20 - $km) ; // closer gets higher score (max 20)
                }
            } else {
                $score += 5.0; // less delivery men -> higher priority
            }
        } catch (\Throwable $e) {
            $this->logger->warning('AI priority distance calc failed: '.$e->getMessage());
        }

        // VIP / order value weight
        $orderTotal = (float) ($delivery->getOrder_total() ?? $delivery->getOrderTotal() ?? 0);
        if ($orderTotal > 0) {
            $score += min(20.0, $orderTotal / 5.0); // scale order value into up to 20 points
        }

        // VIP flag — try to detect VIP in delivery notes (example) or a real customer flag
        $notes = $delivery->getDelivery_notes() ?? $delivery->getDeliveryNotes();
        if ($notes && stripos($notes, 'vip') !== false) {
            $score += 25.0;
        }

        return round($score, 2);
    }

    /**
     * Smart reassignment detection: returns true if delivery stuck.
     */
    public function isStuck(Delivery $delivery, ?int $etaSeconds): bool
    {
        if (!$etaSeconds) return false;
        $created = $delivery->getCreated_at() ?? $delivery->getCreatedAt();
        if (!$created) return false;
        $elapsed = time() - $created->getTimestamp();
        return $elapsed > ($etaSeconds * 1.2);
    }
}
