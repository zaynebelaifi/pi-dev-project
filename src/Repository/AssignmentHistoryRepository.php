<?php

namespace App\Repository;

use App\Entity\AssignmentHistory;
use App\Entity\DeliveryMan;
use App\Entity\FleetCar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AssignmentHistory>
 */
class AssignmentHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssignmentHistory::class);
    }

    public function findActiveByCar(FleetCar $car): ?AssignmentHistory
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.car = :car')
            ->andWhere('a.status = :status')
            ->setParameter('car', $car)
            ->setParameter('status', 'active')
            ->orderBy('a.assigned_at', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByDeliveryMan(DeliveryMan $deliveryMan): ?AssignmentHistory
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.deliveryMan = :deliveryMan')
            ->andWhere('a.status = :status')
            ->setParameter('deliveryMan', $deliveryMan)
            ->setParameter('status', 'active')
            ->orderBy('a.assigned_at', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return AssignmentHistory[]
     */
    public function findLatest(int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.assigned_at', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }
}
