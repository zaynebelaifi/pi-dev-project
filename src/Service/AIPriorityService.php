<?php

namespace App\Service;

use App\Entity\Delivery;
use App\Entity\DeliveryMan;
use App\Entity\FleetCar;
use App\Repository\DeliveryManRepository;
use App\Repository\DeliveryFeatureRepository;
use App\Repository\FleetCarRepository;
use Psr\Log\LoggerInterface;
use App\Contract\AIPriorityServiceInterface;

final class AIPriorityService implements AIPriorityServiceInterface
{
    public function __construct(
        private LogisticsService $logistics,
        private DeliveryManRepository $deliveryManRepo,
        private DeliveryFeatureRepository $featureRepo,
        private \App\Repository\DeliveryRepository $deliveryRepo,
        private FleetCarRepository $carRepo,
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

    /**
     * AI-powered best assignment: ranks all available drivers and cars.
     * Returns the optimal driver+car pair with score and reason.
     *
     * @return array{driver: DeliveryMan, car: FleetCar, score: float, reason: string, candidates: array}
     * @throws \RuntimeException when no driver or car is available
     */
    public function suggestBestAssignment(Delivery $delivery): array
    {
        // 1. Gather available drivers
        $drivers = $this->deliveryManRepo->findAvailableDeliveryMen();
        if (empty($drivers)) {
            throw new \RuntimeException('Aucun livreur disponible pour l\'assignation.');
        }

        // 2. Gather available cars
        $cars = $this->carRepo->findBy(['carStatus' => 'available']);
        if (empty($cars)) {
            // Fallback: try all cars
            $cars = $this->carRepo->findAll();
        }
        if (empty($cars)) {
            throw new \RuntimeException('Aucun véhicule disponible pour l\'assignation.');
        }

        // 3. Reference point: delivery destination or restaurant default
        $refLat = (float) ($delivery->getDestinationLat() ?? $delivery->getCurrentLatitude() ?? 36.8065);
        $refLon = (float) ($delivery->getDestinationLng() ?? $delivery->getCurrentLongitude() ?? 10.1815);

        // 4. Score each driver
        $scoredDrivers = [];
        foreach ($drivers as $dm) {
            $driverScore = $this->scoreDriverForDelivery($delivery, $dm, $refLat, $refLon);

            // Workload penalty: reduce score for drivers with active deliveries
            $activeCount = $dm->getDeliverys()
                ->filter(fn($d) => in_array($d->getStatus(), ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT', 'assigned', 'in_progress'], true))
                ->count();
            $workloadPenalty = $activeCount * 10.0;
            $driverScore = max(0, $driverScore - $workloadPenalty);

            // Performance bonus
            $perfBonus = ($dm->getPerformanceScore() / 100.0) * 5.0;
            $driverScore += $perfBonus;

            $scoredDrivers[] = [
                'driver' => $dm,
                'score'  => round($driverScore, 2),
            ];
        }

        // Sort descending (higher = better)
        usort($scoredDrivers, fn($a, $b) => $b['score'] <=> $a['score']);

        $bestDriver = $scoredDrivers[0]['driver'];
        $bestDriverScore = $scoredDrivers[0]['score'];

        // 5. Find best car (prefer higher fuel, available status)
        $bestCar = $this->findBestCar($cars);

        // 6. Normalize score to 0.0-1.0 range (lower = better for frontend)
        $maxPossibleScore = 100.0 + 5.0; // max rating score + perf bonus
        $normalizedScore = round(1.0 - min(1.0, $bestDriverScore / $maxPossibleScore), 4);

        // 7. Build reason string
        $reason = $this->buildAssignmentReason($bestDriver, $bestCar, $bestDriverScore);

        // 8. Build candidates list (top 3) for the modal
        $candidates = [];
        foreach (array_slice($scoredDrivers, 0, 3) as $c) {
            $candidates[] = [
                'driverId'   => $c['driver']->getId(),
                'driverName' => $c['driver']->getName(),
                'score'      => $c['score'],
                'rating'     => (float) ($c['driver']->getRating() ?? 0),
                'status'     => $c['driver']->getStatus(),
            ];
        }

        return [
            'driver'     => $bestDriver,
            'car'        => $bestCar,
            'score'      => $normalizedScore,
            'reason'     => $reason,
            'candidates' => $candidates,
        ];
    }

    /**
     * Find the best available car (highest fuel level, available first).
     */
    private function findBestCar(array $cars): FleetCar
    {
        usort($cars, function (FleetCar $a, FleetCar $b) {
            // Prefer available cars
            $aAvail = $a->getCarStatus() === 'available' ? 1 : 0;
            $bAvail = $b->getCarStatus() === 'available' ? 1 : 0;
            if ($aAvail !== $bAvail) return $bAvail <=> $aAvail;
            // Then by fuel level
            return ($b->getFuelLevel() ?? 0) <=> ($a->getFuelLevel() ?? 0);
        });
        return $cars[0];
    }

    /**
     * Build a human-readable reason for the AI suggestion.
     */
    private function buildAssignmentReason(DeliveryMan $driver, FleetCar $car, float $score): string
    {
        $rating = $driver->getRating() ?? 'N/A';
        $fuel = $car->getFuelLevel() ?? 100;
        $perf = $driver->getPerformanceScore();

        return sprintf(
            'Meilleur match: %s (score IA: %.1f) — Rating: %s/5, Performance: %.0f/100, '
            . 'Véhicule: %s %s (%s), Carburant: %.0f%%.',
            $driver->getName(),
            $score,
            $rating,
            $perf,
            $car->getMake(),
            $car->getModel(),
            $car->getLicensePlate(),
            $fuel
        );
    }
}
