<?php

namespace App\Service;

use App\Entity\DeliveryMan;
use App\Entity\Delivery;
use App\Repository\DeliveryManRepository;
use Doctrine\ORM\EntityManagerInterface;

class FleetService
{
    public function __construct(
        private DeliveryManRepository $deliveryManRepository,
        private EntityManagerInterface $em
    ) {}

    /**
     * Haversine formula - calculate distance between two GPS points in km
     */
    public function haversineDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $R = 6371; // Earth radius in km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }

    /**
     * Get all delivery men with their current locations
     */
    public function getAllDeliveryMenLocations(): array
    {
        $deliveryMen = $this->deliveryManRepository->findAll();
        
        return array_map(function (DeliveryMan $dm) {
            return [
                'id' => $dm->getDelivery_man_id(),
                'name' => $dm->getName(),
                'email' => $dm->getEmail(),
                'phone' => $dm->getPhone(),
                'latitude' => $dm->getLatitude(),
                'longitude' => $dm->getLongitude(),
                'lastUpdate' => $dm->getLastLocationUpdate()?->format('H:i:s'),
                'status' => $dm->getStatus(),
                'vehicleType' => $dm->getVehicle_type(),
            ];
        }, $deliveryMen);
    }

    /**
     * Find nearest available delivery man to a destination
     */
    public function findNearestAvailableDeliveryMan(
        float $destLat,
        float $destLng
    ): ?DeliveryMan {
        $deliveryMen = $this->deliveryManRepository->findBy(['status' => 'active']);
        
        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($deliveryMen as $dm) {
            if ($dm->getLatitude() === null || $dm->getLongitude() === null) {
                continue;
            }

            $distance = $this->haversineDistance(
                $dm->getLatitude(),
                $dm->getLongitude(),
                $destLat,
                $destLng
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $dm;
            }
        }

        return $nearest;
    }

    /**
     * Calculate ETA in minutes (default speed: 30 km/h)
     */
    public function calculateETA(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
        float $speedKmh = 30
    ): int {
        $distance = $this->haversineDistance($lat1, $lng1, $lat2, $lng2);
        return (int) ceil(($distance / $speedKmh) * 60);
    }

    /**
     * Detect inactive delivery men (no update since X minutes)
     */
    public function detectInactiveDeliveryMen(int $minutes = 15): array
    {
        $allDeliveryMen = $this->deliveryManRepository->findAll();
        $threshold = new \DateTime("-{$minutes} minutes");
        
        return array_filter($allDeliveryMen, function (DeliveryMan $dm) use ($threshold) {
            return $dm->getLastLocationUpdate() === null ||
                   $dm->getLastLocationUpdate() < $threshold;
        });
    }

    /**
     * Get fleet optimization suggestions
     */
    public function getOptimizationSuggestions(): array
    {
        $suggestions = [];
        
        // Check for inactive delivery men
        $inactive = $this->detectInactiveDeliveryMen(15);
        if (count($inactive) > 0) {
            $suggestions[] = [
                'type' => 'warning',
                'icon' => '⚠️',
                'title' => 'Inactive Delivery Men',
                'message' => count($inactive) . ' livreur(s) inactif(s) depuis 15 minutes',
                'affectedIds' => array_map(fn($dm) => $dm->getDelivery_man_id(), $inactive),
            ];
        }

        // Check fleet size
        $all = $this->deliveryManRepository->findAll();
        $active = $this->deliveryManRepository->findBy(['status' => 'active']);
        
        if (count($all) === 0) {
            $suggestions[] = [
                'type' => 'danger',
                'icon' => '❌',
                'title' => 'No Fleet',
                'message' => 'Aucun livreur disponible',
            ];
        } elseif (count($active) === 0) {
            $suggestions[] = [
                'type' => 'danger',
                'icon' => '❌',
                'title' => 'No Active Drivers',
                'message' => 'Aucun livreur actif',
            ];
        } elseif (count($active) < 3) {
            $suggestions[] = [
                'type' => 'info',
                'icon' => 'ℹ️',
                'title' => 'Small Fleet',
                'message' => 'Flotte réduite : ' . count($active) . ' livreur(s) actif(s)',
            ];
        } else {
            $suggestions[] = [
                'type' => 'success',
                'icon' => '✅',
                'title' => 'Fleet Status Good',
                'message' => 'Flotte opérationnelle : ' . count($active) . ' livreurs actifs',
            ];
        }

        // Check pending deliveries
        $repo = $this->em->getRepository(Delivery::class);
        $pending = $repo->count(['status' => 'pending']);
        if ($pending > 5) {
            $suggestions[] = [
                'type' => 'warning',
                'icon' => '🚚',
                'title' => 'High Pending Orders',
                'message' => $pending . ' commandes en attente d\'assignation',
            ];
        }

        return $suggestions;
    }

    /**
     * Update delivery man GPS location
     */
    public function updateLocation(
        DeliveryMan $deliveryMan,
        float $latitude,
        float $longitude
    ): void {
        $deliveryMan->setLatitude($latitude);
        $deliveryMan->setLongitude($longitude);
        $deliveryMan->setLastLocationUpdate(new \DateTime());
        $this->em->flush();
    }

    /**
     * Assign delivery to nearest delivery man
     */
    public function assignDeliveryToNearest(
        Delivery $delivery,
        float $destLat,
        float $destLng
    ): ?DeliveryMan {
        $nearest = $this->findNearestAvailableDeliveryMan($destLat, $destLng);
        if ($nearest === null) {
            return null;
        }

        $delivery->setDestination_latitude($destLat);
        $delivery->setDestination_longitude($destLng);
        $delivery->setDeliveryMan($nearest);
        $delivery->setStatus('assigned');

        if ($nearest->getLatitude() && $nearest->getLongitude()) {
            $eta = $this->calculateETA(
                $nearest->getLatitude(),
                $nearest->getLongitude(),
                $destLat,
                $destLng
            );
            $delivery->setEstimated_time($eta);
        }

        $this->em->flush();

        return $nearest;
    }

    /**
     * Calculate distance between two delivery men
     */
    public function getDistanceBetweenDeliveryMen(
        DeliveryMan $dm1,
        DeliveryMan $dm2
    ): ?float {
        if (!$dm1->getLatitude() || !$dm1->getLongitude() ||
            !$dm2->getLatitude() || !$dm2->getLongitude()) {
            return null;
        }

        return $this->haversineDistance(
            $dm1->getLatitude(),
            $dm1->getLongitude(),
            $dm2->getLatitude(),
            $dm2->getLongitude()
        );
    }
}
