<?php

namespace App\Service;

use App\Entity\DeliveryMan;
use App\Entity\FleetCar;
use App\Entity\GPSLog;
use App\Repository\GPSLogRepository;
use Doctrine\ORM\EntityManagerInterface;

class GPSService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GPSLogRepository $gpsLogRepository,
        private DistanceCalculator $distanceCalculator,
    ) {
    }

    public function updateLocation(
        FleetCar $car,
        float $latitude,
        float $longitude,
        ?DeliveryMan $deliveryMan = null,
        ?int $accuracy = null,
        ?float $speed = null,
    ): GPSLog {
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new \InvalidArgumentException('INVALID_COORDINATES');
        }

        $now = new \DateTimeImmutable();

        $log = (new GPSLog())
            ->setCar($car)
            ->setDeliveryMan($deliveryMan)
            ->setLatitude($latitude)
            ->setLongitude($longitude)
            ->setAccuracy($accuracy)
            ->setSpeed($speed)
            ->setTimestamp($now)
            ->setSource('gps');

        $car->setLatitude($latitude)
            ->setLongitude($longitude)
            ->setLastUpdate($now)
            ->setUpdatedAt($now);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $log;
    }

    /**
     * @return GPSLog[]
     */
    public function getLocationHistory(FleetCar $car, int $limit = 100, int $offset = 0): array
    {
        return $this->gpsLogRepository->findHistoryForCar($car, $limit, $offset);
    }

    public function getDistanceTraveled(FleetCar $car, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): float
    {
        $qb = $this->gpsLogRepository->createQueryBuilder('g')
            ->andWhere('g.car = :car')
            ->setParameter('car', $car)
            ->orderBy('g.timestamp', 'ASC');

        if ($from !== null) {
            $qb->andWhere('g.timestamp >= :from')->setParameter('from', $from);
        }

        if ($to !== null) {
            $qb->andWhere('g.timestamp <= :to')->setParameter('to', $to);
        }

        $points = $qb->getQuery()->getResult();
        if (count($points) < 2) {
            return 0.0;
        }

        $distance = 0.0;
        for ($i = 1; $i < count($points); $i++) {
            $prev = $points[$i - 1];
            $curr = $points[$i];
            $distance += $this->distanceCalculator->haversineDistance(
                (float) $prev->getLatitude(),
                (float) $prev->getLongitude(),
                (float) $curr->getLatitude(),
                (float) $curr->getLongitude()
            );
        }

        return $distance;
    }
}
