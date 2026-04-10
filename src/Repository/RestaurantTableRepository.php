<?php

namespace App\Repository;

use App\Entity\RestaurantTable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RestaurantTableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RestaurantTable::class);
    }

    public function searchAndSort(string $search = '', string $sort = 'table_id', string $direction = 'ASC'): array
    {
        $allowed = ['table_id', 'capacity', 'status'];
        if (!in_array($sort, $allowed)) $sort = 'table_id';
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $this->createQueryBuilder('t');

        if ($search !== '') {
            $qb->where('t.status LIKE :s OR t.capacity LIKE :s')
               ->setParameter('s', "%$search%");
        }

        return $qb->orderBy("t.$sort", $direction)->getQuery()->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.table_id)')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->getQuery()->getSingleScalarResult();
    }

    public function countOccupied(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.table_id)')
            ->where('t.status IN (:statuses)')
            ->setParameter('statuses', ['occupied', 'reserved'])
            ->getQuery()->getSingleScalarResult();
    }
}