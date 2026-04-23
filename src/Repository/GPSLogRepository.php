<?php

namespace App\Repository;

use App\Entity\FleetCar;
use App\Entity\GPSLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GPSLog>
 */
class GPSLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GPSLog::class);
    }

    /**
     * @return GPSLog[]
     */
    public function findHistoryForCar(FleetCar $car, int $limit = 100, int $offset = 0): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.car = :car')
            ->setParameter('car', $car)
            ->orderBy('g.timestamp', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    public function findLatestForCar(FleetCar $car): ?GPSLog
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.car = :car')
            ->setParameter('car', $car)
            ->orderBy('g.timestamp', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
