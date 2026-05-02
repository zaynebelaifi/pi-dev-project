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

    public function findBookedTableIdsAt(
        \DateTimeInterface $date,
        \DateTimeInterface $time,
        array $statuses = ['CONFIRMED', 'PENDING']
    ): array {
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.table) AS tableId')
            ->where('r.reservationDate = :date')
            ->andWhere('r.reservationTime = :time')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('date', $date)
            ->setParameter('time', $time)
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getScalarResult();

        return array_values(array_unique(array_map(static fn (array $row) => (int) $row['tableId'], $rows)));
    }

    public function isTableBookedAt(
        int $tableId,
        \DateTimeInterface $date,
        \DateTimeInterface $time,
        array $statuses = ['CONFIRMED', 'PENDING']
    ): bool {
        $count = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.table = :tableId')
            ->andWhere('r.reservationDate = :date')
            ->andWhere('r.reservationTime = :time')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('tableId', $tableId)
            ->setParameter('date', $date)
            ->setParameter('time', $time)
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function getClientTablePreferenceCounts(int $clientId): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.table) AS tableId, COUNT(r.id) AS uses')
            ->where('r.clientId = :clientId')
            ->andWhere('r.status = :status')
            ->setParameter('clientId', $clientId)
            ->setParameter('status', 'CONFIRMED')
            ->groupBy('r.table')
            ->getQuery()
            ->getScalarResult();

        $preferences = [];
        foreach ($rows as $row) {
            $preferences[(int) $row['tableId']] = (int) $row['uses'];
        }

        return $preferences;
    }
}