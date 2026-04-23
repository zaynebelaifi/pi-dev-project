<?php
namespace App\Service;

use App\Entity\Delivery;
use App\Repository\DeliveryManRepository;
use App\Repository\DeliveryFeatureRepository;
use Psr\Log\LoggerInterface;

final class AIPriorityService
{
    public function __construct(
        private LogisticsService $logistics,
        private DeliveryManRepository $deliveryManRepo,
        private DeliveryFeatureRepository $featureRepo,
        private \App\Repository\DeliveryRepository $deliveryRepo,
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

        // Persist computed features for later ML / analysis
        try {
            $features = [
                'wait_seconds' => $waitSeconds ?? null,
                'eta_seconds' => $eta['duration'] ?? null,
                'distance_m' => $eta['distance'] ?? null,
                'order_total' => $orderTotal,
                'vip' => ($notes && stripos($notes, 'vip') !== false) ? 1 : 0,
                'nearest_delivery_man_id' => $nearest ? ($nearest->getId() ?? $nearest->getDeliveryManId() ?? null) : null,
                'calculated_score' => round($score, 2),
                'created_at' => (new \DateTimeImmutable())->format('c'),
            ];

            $feature = new \App\Entity\DeliveryFeature();
            $feature->setDelivery($delivery);
            $feature->setFeatures($features);
            $feature->setCreatedAt(new \DateTimeImmutable());
            $this->featureRepo->save($feature, true);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to persist delivery features: '.$e->getMessage());
        }

        return round($score, 2);
    }

    /**
     * Compute a combined score for a delivery man relative to a reference point.
     * Higher is better. Uses driver rating (0-5) and distance (km) from reference.
     */
    public function scoreDriverForDelivery(Delivery $delivery, \App\Entity\DeliveryMan $dm, float $refLat = 35.032, float $refLon = 9.470): float
    {
        $rating = (float) ($dm->getRating() ?? 0.0);

        $loc = $this->getDeliveryManLastKnownLocation($dm);
        $driverLat = $loc['lat'] ?? null;
        $driverLon = $loc['lon'] ?? null;

        return self::computeScoreFromParams($rating, $driverLat, $driverLon, $refLat, $refLon);
    }

    public static function computeScoreFromParams(float $rating, ?float $driverLat, ?float $driverLon, float $refLat, float $refLon): float
    {
        // rating contributes up to 50 points
        $ratingScore = min(5.0, max(0.0, $rating)) / 5.0 * 50.0;

        // if we don't have driver location, penalize heavily
        if ($driverLat === null || $driverLon === null) {
            $distanceKm = 100.0;
        } else {
            $earthRadius = 6371000.0;
            $dLat = deg2rad($refLat - $driverLat);
            $dLon = deg2rad($refLon - $driverLon);
            $a = sin($dLat / 2) * sin($dLat / 2)
                + cos(deg2rad($driverLat)) * cos(deg2rad($refLat))
                * sin($dLon / 2) * sin($dLon / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distanceKm = ($earthRadius * $c) / 1000.0;
        }

        // distance contributes up to 50 points (closer -> higher). We cap at 50km.
        $distanceScore = max(0.0, 50.0 - $distanceKm);

        return round($ratingScore + $distanceScore, 2);
    }

    private function getDeliveryManLastKnownLocation(\App\Entity\DeliveryMan $dm): array
    {
        try {
            $last = $this->deliveryRepo->createQueryBuilder('d')
                ->andWhere('d.deliveryMan = :dm')
                ->andWhere('d.current_latitude IS NOT NULL')
                ->andWhere('d.current_longitude IS NOT NULL')
                ->setParameter('dm', $dm)
                ->orderBy('d.created_at', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($last) {
                return [
                    'lat' => (float) $last->getCurrentLatitude(),
                    'lon' => (float) $last->getCurrentLongitude(),
                ];
            }
        } catch (\Throwable $e) {
            // ignore and return empty
        }

        return [];
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
