<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function searchAndSort(string $search = '', string $sort = 'reservationDate', string $direction = 'DESC'): array
    {
        $allowed = ['reservationDate', 'reservationTime', 'numberOfGuests', 'status', 'clientId'];
        if (!in_array($sort, $allowed)) $sort = 'reservationDate';
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('r');

        if ($search !== '') {
            $qb->andWhere('r.status LIKE :s OR CAST(r.clientId AS string) LIKE :s')
               ->setParameter('s', "%$search%");
        }

        return $qb->orderBy("r.$sort", $direction)
                  ->getQuery()
                  ->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}