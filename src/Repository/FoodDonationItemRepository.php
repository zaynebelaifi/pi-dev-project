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
