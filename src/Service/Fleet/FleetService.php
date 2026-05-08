<?php

declare(strict_types=1);

namespace App\Service\Fleet;

use App\Contract\FleetServiceInterface;
use App\Entity\Delivery;
use App\Entity\DeliveryMan;
use App\Entity\FleetCar;
use App\Repository\DeliveryManRepository;
use App\Repository\DeliveryRepository;
use App\Repository\FleetCarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * FleetService — core logic for live fleet management.
 *
 * Responsibilities:
 *  - Aggregating fleet status (drivers, cars, active deliveries)
 *  - Finding nearby drivers using the Haversine formula
 *  - Scoring and ranking drivers for smart assignment
 *  - Persisting location updates from drivers
 *  - Assigning drivers and cars to deliveries
 */
class FleetService implements FleetServiceInterface
{
    /** Earth radius in kilometres, used by the Haversine formula. */
    private const EARTH_RADIUS_KM = 6371.0;

    public function __construct(
        private readonly DeliveryManRepository $deliveryManRepo,
        private readonly FleetCarRepository    $carRepo,
        private readonly DeliveryRepository    $deliveryRepo,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface        $logger,
        private readonly float                  $restaurantLat,
        private readonly float                  $restaurantLng,
    ) {}

    // ── Public API ──────────────────────────────────────────────────────────

    /**
     * Returns the full fleet status snapshot used by the dashboard.
     */
    public function getFleetStatus(): array
    {
        try {
            $drivers   = $this->deliveryManRepo->findAll();
            $cars      = $this->carRepo->findAll();
            $active    = $this->deliveryRepo->findBy(['status' => ['ASSIGNED', 'IN_PROGRESS']]);
            $pending   = $this->deliveryRepo->findBy(['status' => 'PENDING']);

            return [
                'drivers'  => array_map(fn(DeliveryMan $d) => $this->serializeDriver($d), $drivers),
                'cars'     => array_map(fn(FleetCar $c) => $this->serializeCar($c), $cars),
                'active'   => count($active),
                'pending'  => count($pending),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('[Fleet] getFleetStatus failed: ' . $e->getMessage());
            return ['drivers' => [], 'cars' => [], 'active' => 0, 'pending' => 0];
        }
    }

    /**
     * Returns all active driver locations for live map refresh.
     *
     * @return list<array{id: int, name: string, lat: float, lng: float, status: string, activeDeliveries: int}>
     */
    public function getActiveDriverLocations(): array
    {
        $drivers = $this->deliveryManRepo->findAll();
        $result  = [];

        foreach ($drivers as $driver) {
            $result[] = [
                'id'               => $driver->getId(),
                'name'             => $driver->getName(),
                'lat'              => $driver->getLatitude() ?? $this->restaurantLat,
                'lng'              => $driver->getLongitude() ?? $this->restaurantLng,
                'status'           => $driver->getStatus() ?? 'active',
                'isOnline'         => $driver->isOnline(),
                'activeDeliveries' => $driver->getActiveDeliveries(),
                'rating'           => (float) ($driver->getRating() ?? 0),
                'lastUpdate'       => $driver->getLastLocationUpdate()?->format('H:i:s') ?? 'unknown',
            ];
        }

        return $result;
    }

    /**
     * Update a driver's GPS coordinates in the database.
     */
    public function updateDriverLocation(int $driverId, float $lat, float $lng): void
    {
        $driver = $this->deliveryManRepo->find($driverId);
        if (!$driver) {
            $this->logger->warning("[Fleet] updateDriverLocation: driver #{$driverId} not found.");
            return;
        }

        $driver->setLatitude($lat);
        $driver->setLongitude($lng);
        $driver->setIsOnline(true);
        $driver->setLastLocationUpdate(new \DateTime());
        $this->em->flush();
    }

    /**
     * Find drivers ordered by distance from the restaurant, with scoring.
     *
     * Score formula: distance_km + (active_deliveries * 2)
     * Lowest score = best candidate.
     *
     * @return list<array{driver: DeliveryMan, distance: float, score: float}>
     */
    public function findNearbyDrivers(): array
    {
        $drivers = $this->deliveryManRepo->findBy(['status' => 'active']);
        $scored  = [];

        foreach ($drivers as $driver) {
            $driverLat = $driver->getLatitude() ?? $this->restaurantLat;
            $driverLng = $driver->getLongitude() ?? $this->restaurantLng;

            $distance = $this->calculateDistance(
                $this->restaurantLat, $this->restaurantLng,
                $driverLat, $driverLng
            );

            $score = $distance + ($driver->getActiveDeliveries() * 2.0);

            $scored[] = [
                'driver'    => $driver,
                'distance'  => round($distance, 2),
                'score'     => round($score, 2),
                'etaMin'    => $this->estimateEtaMinutes($distance),
            ];
        }

        usort($scored, fn($a, $b) => $a['score'] <=> $b['score']);

        return $scored;
    }

    /**
     * Return the best available driver (lowest score).
     */
    public function getBestDriver(): ?DeliveryMan
    {
        $ranked = $this->findNearbyDrivers();
        return $ranked[0]['driver'] ?? null;
    }

    /**
     * Assign a driver and car to a delivery.
     */
    public function assignDriverToDelivery(int $deliveryId, int $driverId, int $carId): array
    {
        $delivery = $this->deliveryRepo->find($deliveryId);
        $driver   = $this->deliveryManRepo->find($driverId);
        $car      = $this->carRepo->find($carId);

        if (!$delivery || !$driver || !$car) {
            return ['success' => false, 'message' => 'Delivery, driver, or car not found.'];
        }

        // Link driver to delivery
        $delivery->setDeliveryMan($driver);
        $delivery->setStatus('ASSIGNED');

        // Increment driver workload
        $driver->setActiveDeliveries($driver->getActiveDeliveries() + 1);

        // Mark car as in-use
        $car->setCarStatus('in_use');
        $car->setDeliveryManId($driver->getId());

        $this->em->flush();

        $this->logger->info("[Fleet] Delivery #{$deliveryId} assigned to driver #{$driverId} with car #{$carId}.");

        return [
            'success' => true,
            'message' => "Driver {$driver->getName()} assigned successfully.",
            'delivery_id' => $deliveryId,
            'driver_name' => $driver->getName(),
            'car'         => $car->getMake() . ' ' . $car->getModel(),
        ];
    }

    /**
     * Haversine formula — great-circle distance between two lat/lng points in km.
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }

    /**
     * Estimate delivery ETA in minutes based on distance.
     * Uses average urban speed of 25 km/h.
     */
    public function estimateEtaMinutes(float $distanceKm): int
    {
        $avgSpeedKmPerHour = 25.0;
        return (int) ceil(($distanceKm / $avgSpeedKmPerHour) * 60);
    }

    // ── Serializers ─────────────────────────────────────────────────────────

    private function serializeDriver(DeliveryMan $d): array
    {
        $lat = $d->getLatitude() ?? $this->restaurantLat;
        $lng = $d->getLongitude() ?? $this->restaurantLng;

        $distance = $this->calculateDistance($this->restaurantLat, $this->restaurantLng, $lat, $lng);

        return [
            'id'               => $d->getId(),
            'name'             => $d->getName(),
            'phone'            => $d->getPhone(),
            'status'           => $d->getStatus() ?? 'active',
            'isOnline'         => $d->isOnline(),
            'lat'              => $lat,
            'lng'              => $lng,
            'activeDeliveries' => $d->getActiveDeliveries(),
            'rating'           => (float) ($d->getRating() ?? 0),
            'distance'         => round($distance, 2),
            'vehicleType'      => $d->getVehicleType(),
            'vehicleNumber'    => $d->getVehicleNumber(),
            'score'            => round($distance + ($d->getActiveDeliveries() * 2), 2),
        ];
    }

    private function serializeCar(FleetCar $c): array
    {
        return [
            'id'          => $c->getId(),
            'make'        => $c->getMake(),
            'model'       => $c->getModel(),
            'plate'       => $c->getLicensePlate(),
            'type'        => $c->getVehicleType(),
            'status'      => $c->getCarStatus(),
            'fuelLevel'   => $c->getFuelLevel() ?? 100,
            'driverId'    => $c->getDeliveryManId(),
        ];
    }
}
