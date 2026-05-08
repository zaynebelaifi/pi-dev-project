<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Delivery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function searchAndSort(string $search = '', string $sort = 'order_date', string $direction = 'DESC'): array
    {
        $allowed = ['order_date', 'order_type', 'status', 'total_amount', 'client'];
        if (!in_array($sort, $allowed, true)) {
            $sort = 'order_date';
        }
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.reservation', 'r')
            ->leftJoin('o.client', 'c')
            ->leftJoin('App\Entity\Delivery', 'd', 'WITH', 'd.order_id = o.order_id')
            ->addSelect('r', 'c');

        if ($search !== '') {
            $qb->andWhere('o.status LIKE :s OR o.order_type LIKE :s OR c.id LIKE :s OR c.first_name LIKE :s OR c.last_name LIKE :s OR o.delivery_address LIKE :s OR d.recipient_name LIKE :s OR d.recipient_phone LIKE :s')
               ->setParameter('s', "%$search%");
        }

        return $qb->orderBy("o.$sort", $direction)->getQuery()->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.order_id)')
            ->where('o.status = :status')
            ->setParameter('status', $status)
            ->getQuery()->getSingleScalarResult();
    }

    public function getTotalRevenue(): float
    {
        return (float) $this->createQueryBuilder('o')
            ->select('SUM(o.total_amount)')
            ->where('o.status = :status')
            ->setParameter('status', 'DELIVERED')
            ->getQuery()->getSingleScalarResult() ?? 0.0;
    }
}
