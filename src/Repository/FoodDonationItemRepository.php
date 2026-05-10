<?php

namespace App\Repository;

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
        $qb = $this->createQueryBuilder('f');

        if ($search !== null && trim($search) !== '') {
            $search = trim($search);
            if (is_numeric($search)) {
                $qb->andWhere('f.donation_event_id = :searchInt OR f.item_id = :searchInt OR f.quantity = :searchInt')
                    ->setParameter('searchInt', (int) $search);
            }
        }

        $allowedSort = ['donation_event_id', 'item_id', 'quantity'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'donation_event_id';
        }

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy('f.'.$sort, $direction);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, array{donation_event_id:int, item_id:int, quantity:int, item_name:string|null}>
     */
    public function findItemsWithDishNames(int $eventId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT fdi.donation_event_id, fdi.item_id, fdi.quantity, d.name AS item_name '
            .'FROM food_donation_items fdi '
            .'LEFT JOIN dish d ON fdi.item_id = d.id '
            .'WHERE fdi.donation_event_id = :eventId '
            .'ORDER BY fdi.item_id';

        return $conn->fetchAllAssociative($sql, ['eventId' => $eventId]);
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
