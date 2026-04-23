<?php

namespace App\Repository;

use App\Entity\Dish;
use App\Entity\FoodDonationItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FoodDonationItem>
 */
class FoodDonationItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoodDonationItem::class);
    }

    public function findFilteredItems(?string $search, string $sort, string $direction): array
    {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin(Dish::class, 'dish', 'WITH', 'dish.id = f.item_id');

        if ($search !== null && trim($search) !== '') {
            $search = trim($search);
            if (is_numeric($search)) {
                $qb->andWhere('f.donation_event_id = :searchInt OR f.item_id = :searchInt OR f.quantity = :searchInt OR LOWER(dish.name) LIKE :searchText')
                    ->setParameter('searchInt', (int) $search)
                    ->setParameter('searchText', '%'.strtolower($search).'%');
            } else {
                $qb->andWhere('LOWER(dish.name) LIKE :searchText')
                    ->setParameter('searchText', '%'.strtolower($search).'%');
            }
        }

        $allowedSort = ['donation_event_id', 'item_id', 'item_name', 'quantity'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'donation_event_id';
        }

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        if ($sort === 'item_name') {
            $qb->orderBy('dish.name', $direction);
        } else {
            $qb->orderBy('f.'.$sort, $direction);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByDonationEventId(int $eventId): array
    {
        return $this->createQueryBuilder('f')
            ->select('f.item_id AS itemId', 'f.quantity AS quantity', 'dish.name AS dishName')
            ->leftJoin(Dish::class, 'dish', 'WITH', 'dish.id = f.item_id')
            ->andWhere('f.donation_event_id = :eventId')
            ->setParameter('eventId', $eventId)
            ->orderBy('dish.name', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param int[] $eventIds
     * @return array<int, array<int, array{itemId: int, quantity: int, dishName: string}>>
     */
    public function findGroupedByEventIds(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('f')
            ->select('f.donation_event_id AS eventId', 'f.item_id AS itemId', 'f.quantity AS quantity', 'dish.name AS dishName')
            ->leftJoin(Dish::class, 'dish', 'WITH', 'dish.id = f.item_id')
            ->andWhere('f.donation_event_id IN (:eventIds)')
            ->setParameter('eventIds', $eventIds)
            ->orderBy('f.donation_event_id', 'ASC')
            ->addOrderBy('dish.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $groupedItems = [];
        foreach ($rows as $row) {
            $eventId = (int) $row['eventId'];
            $groupedItems[$eventId][] = [
                'itemId' => (int) $row['itemId'],
                'quantity' => (int) $row['quantity'],
                'dishName' => (string) ($row['dishName'] ?? 'Unknown item'),
            ];
        }

        return $groupedItems;
    }

    /**
     * @param int[] $eventIds
     * @return array<int, int>
     */
    public function countByEventIds(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('f')
            ->select('f.donation_event_id AS eventId, COUNT(f.item_id) AS itemsCount')
            ->andWhere('f.donation_event_id IN (:eventIds)')
            ->setParameter('eventIds', $eventIds)
            ->groupBy('f.donation_event_id')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['eventId']] = (int) $row['itemsCount'];
        }

        return $counts;
    }

    //    /**
    //     * @return FoodDonationItem[] Returns an array of FoodDonationItem objects
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

    //    public function findOneBySomeField($value): ?FoodDonationItem
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
