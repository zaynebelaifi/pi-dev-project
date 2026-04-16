<?php

namespace App\Repository;

use App\Entity\Delivery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;

/**
 * @extends ServiceEntityRepository<Delivery>
 */
class DeliveryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Delivery::class);
    }

    /**
     * Find nearby pending orders by coordinates within radius meters.
     * Returns array of Delivery objects.
     */
    public function findNearbyOrdersByCoords(float $lat, float $lon, int $radiusMeters = 1000): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT * FROM delivery WHERE status IN ("PENDING","ASSIGNED") AND current_latitude IS NOT NULL AND current_longitude IS NOT NULL';
        $rows = $conn->fetchAllAssociative($sql);
        $result = [];
        foreach ($rows as $r) {
            $dLat = (float)$r['current_latitude'];
            $dLon = (float)$r['current_longitude'];
            // simple haversine
            $earthRadius = 6371000;
            $dLatR = deg2rad($dLat - $lat);
            $dLonR = deg2rad($dLon - $lon);
            $a = sin($dLatR/2) * sin($dLatR/2) + cos(deg2rad($lat)) * cos(deg2rad($dLat)) * sin($dLonR/2) * sin($dLonR/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            $dist = $earthRadius * $c;
            if ($dist <= $radiusMeters) {
                $result[] = $this->find($r['delivery_id']);
            }
        }
        return $result;
    }

    //    /**
    //     * @return Delivery[] Returns an array of Delivery objects
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

    //    public function findOneBySomeField($value): ?Delivery
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findActiveByRecipientPhone(string $phone): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.recipient_phone = :phone')
            ->andWhere('d.status != :delivered')
            ->setParameter('phone', preg_replace('/[^0-9+]/', '', $phone))
            ->setParameter('delivered', 'DELIVERED')
            ->orderBy('d.created_at', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findDeliveredByRecipientPhone(string $phone): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.recipient_phone = :phone')
            ->andWhere('d.status = :delivered')
            ->setParameter('phone', preg_replace('/[^0-9+]/', '', $phone))
            ->setParameter('delivered', 'DELIVERED')
            ->orderBy('d.created_at', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findLatestByRecipientName(string $recipientName): ?Delivery
    {
        return $this->createQueryBuilder('d')
            ->andWhere('LOWER(d.recipient_name) = :name')
            ->setParameter('name', mb_strtolower($recipientName))
            ->orderBy('d.created_at', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByDeliveryManId(int $deliveryManId): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.deliveryMan', 'dm')
            ->andWhere('dm.delivery_man_id = :id')
            ->setParameter('id', $deliveryManId)
            ->orderBy('d.created_at', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function searchAndSort(?string $search, ?string $sortField, ?string $sortDirection): array
    {
        $allowedSorts = [
            'delivery_id' => 'd.delivery_id',
            'order_id' => 'd.order_id',
            'status' => 'd.status',
            'created_at' => 'd.created_at',
            'recipient_name' => 'd.recipient_name',
        ];

        $direction = strtoupper($sortDirection ?? 'DESC');
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'DESC';
        }

        $qb = $this->createQueryBuilder('d');

        if ($search) {
            $qb->andWhere('d.order_id LIKE :search OR d.delivery_address LIKE :search OR d.recipient_name LIKE :search OR d.recipient_phone LIKE :search OR d.status LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $sort = $allowedSorts[$sortField] ?? 'd.created_at';
        $qb->orderBy($sort, $direction);

        return $qb->getQuery()->getResult();
    }

    public function findByOrderIds(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        return $this->createQueryBuilder('d')
            ->andWhere('d.order_id IN (:orderIds)')
            ->setParameter('orderIds', array_values(array_unique($orderIds)))
            ->getQuery()
            ->getResult();
    }
}

