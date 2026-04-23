<?php

namespace App\Repository;

use App\Entity\DonationEventItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DonationEventItem>
 */
class DonationEventItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DonationEventItem::class);
    }

    /**
     * @return DonationEventItem[]
     */
    public function findByEventOrdered(int $eventId): array
    {
        return $this->createQueryBuilder('dei')
            ->innerJoin('dei.item', 'dish')
            ->addSelect('dish')
            ->andWhere('IDENTITY(dei.event) = :eventId')
            ->setParameter('eventId', $eventId)
            ->orderBy('dish.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{itemId:int, quantity:int, name:string, assignmentId:int}>
     */
    public function findGroupedItemsForEvent(int $eventId): array
    {
        $rows = $this->createQueryBuilder('dei')
            ->select('dei.id AS assignmentId', 'dei.quantity AS quantity', 'dish.id AS itemId', 'dish.name AS name')
            ->innerJoin('dei.item', 'dish')
            ->andWhere('IDENTITY(dei.event) = :eventId')
            ->setParameter('eventId', $eventId)
            ->orderBy('dish.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'assignmentId' => (int) $row['assignmentId'],
            'itemId' => (int) $row['itemId'],
            'quantity' => (int) $row['quantity'],
            'name' => (string) $row['name'],
        ], $rows);
    }
}
