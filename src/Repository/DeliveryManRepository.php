<?php

namespace App\Repository;

use App\Entity\DeliveryMan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeliveryMan>
 */
class DeliveryManRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryMan::class);
    }

    //    /**
    //     * @return DeliveryMan[] Returns an array of DeliveryMan objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DeliveryMan
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Find available delivery men (online and not currently in delivery)
     */
    public function findAvailableDeliveryMen(): array
    {
        $available = $this->createQueryBuilder('dm')
            ->leftJoin('dm.deliverys', 'd', 'WITH', 'd.status IN (:inDelivery)')
            ->andWhere('LOWER(dm.status) = :active')
            ->andWhere('d.delivery_id IS NULL')
            ->setParameter('active', 'active')
            ->setParameter('inDelivery', ['ASSIGNED', 'PICKED_UP', 'IN_TRANSIT'])
            ->orderBy('dm.rating', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        if (empty($available)) {
            return $this->createQueryBuilder('dm')
                ->andWhere('LOWER(dm.status) = :active')
                ->setParameter('active', 'active')
                ->orderBy('dm.rating', 'DESC')
                ->getQuery()
                ->getResult()
            ;
        }

        return $available;
    }

    public function searchAndSort(?string $search, ?string $sortField, ?string $sortDirection): array
    {
        $allowedSorts = [
            'delivery_man_id' => 'dm.delivery_man_id',
            'name' => 'dm.name',
            'status' => 'dm.status',
            'date_of_joining' => 'dm.date_of_joining',
            'rating' => 'dm.rating',
        ];

        $direction = strtoupper($sortDirection ?? 'DESC');
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'DESC';
        }

        $qb = $this->createQueryBuilder('dm');

        if ($search) {
            $qb->andWhere('dm.name LIKE :search OR dm.email LIKE :search OR dm.phone LIKE :search OR dm.vehicle_type LIKE :search OR dm.vehicle_number LIKE :search OR dm.status LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $sort = $allowedSorts[$sortField] ?? 'dm.date_of_joining';
        $qb->orderBy($sort, $direction);

        return $qb->getQuery()->getResult();
    }
}
