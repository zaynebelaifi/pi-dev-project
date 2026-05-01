<?php

namespace App\Repository;

use App\Entity\FoodDonationEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FoodDonationEvent>
 */
class FoodDonationEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoodDonationEvent::class);
    }

    /**
     * @return FoodDonationEvent[]
     */
    public function findEventsStartingWithinNextHourWithoutReminder(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.event_date >= :fromTime')
            ->andWhere('e.event_date <= :toTime')
            ->andWhere('e.sms_reminder_sent = :sent')
            ->andWhere('LOWER(e.status) != :cancelled')
            ->setParameter('fromTime', $from)
            ->setParameter('toTime', $to)
            ->setParameter('sent', false)
            ->setParameter('cancelled', 'cancelled')
            ->orderBy('e.event_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFilteredEvents(?string $search, ?string $status, string $sort, string $direction): array
    {
        $qb = $this->createQueryBuilder('f');

        if ($search !== null && trim($search) !== '') {
            $search = trim($search);
            $expr = $qb->expr();
            $searchConditions = $expr->orX(
                $expr->like('f.charity_name', ':search'),
                $expr->like('f.status', ':search')
            );
            $qb->setParameter('search', '%'.$search.'%');

            if (is_numeric($search)) {
                $qb->andWhere($expr->orX($searchConditions, 'f.donation_event_id = :searchInt', 'f.total_quantity = :searchInt'))
                   ->setParameter('searchInt', (int) $search);
            } else {
                $qb->andWhere($searchConditions);
            }
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('f.status = :status')
                ->setParameter('status', $status);
        }

        $allowedSort = ['donation_event_id', 'event_date', 'total_quantity', 'charity_name', 'status', 'updated_at'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'event_date';
        }

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy('f.'.$sort, $direction);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int[] $excludeEventIds
     * @return FoodDonationEvent[]
     */
    public function findRecommendationCandidates(array $excludeEventIds = [], int $limit = 12): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.event_date >= :today')
            ->andWhere('LOWER(e.status) IN (:statuses)')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('statuses', ['scheduled', 'in progress', 'in_progress', 'pending'])
            ->orderBy('e.event_date', 'ASC')
            ->setMaxResults($limit);

        if ($excludeEventIds !== []) {
            $qb->andWhere('e.donation_event_id NOT IN (:excludeEventIds)')
                ->setParameter('excludeEventIds', $excludeEventIds);
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return FoodDonationEvent[] Returns an array of FoodDonationEvent objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?FoodDonationEvent
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
