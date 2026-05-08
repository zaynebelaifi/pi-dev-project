<?php

namespace App\Repository;

use App\Entity\FleetAlert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FleetAlert>
 */
class FleetAlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FleetAlert::class);
    }

    /**
     * @return FleetAlert[]
     */
    public function findRecentUnacknowledged(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.acknowledged = false')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
