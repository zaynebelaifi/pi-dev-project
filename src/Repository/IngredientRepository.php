<?php

namespace App\Repository;

use App\Entity\Ingredient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ingredient>
 */
class IngredientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ingredient::class);
    }

    /**
     * @return Ingredient[]
     */
    public function findForAdminList(?string $search = null): array
    {
        $qb = $this->createQueryBuilder('i');

        if (null !== $search && '' !== trim($search)) {
            $qb
                ->andWhere('LOWER(i.name) LIKE :q OR LOWER(i.unit) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower(trim($search)).'%');
        }

        return $qb
            ->orderBy('i.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countLowStock(): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.quantityInStock <= i.minStockLevel')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countExpired(\DateTimeInterface $today): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.expiryDate < :today')
            ->setParameter('today', $today->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumInventoryValue(): float
    {
        return (float) $this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.quantityInStock * i.unitCost), 0)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Ingredient[]
     */
    public function findExpiredWithStock(\DateTimeInterface $today): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.expiryDate < :today')
            ->andWhere('i.quantityInStock > 0')
            ->setParameter('today', $today->format('Y-m-d'))
            ->orderBy('i.expiryDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByNormalizedNameAndUnit(string $name, string $unit, ?int $excludeId = null): ?Ingredient
    {
        $qb = $this->createQueryBuilder('i')
            ->andWhere('LOWER(TRIM(i.name)) = :name')
            ->andWhere('LOWER(TRIM(i.unit)) = :unit')
            ->setParameter('name', mb_strtolower(trim($name)))
            ->setParameter('unit', mb_strtolower(trim($unit)))
            ->setMaxResults(1);

        if (null !== $excludeId) {
            $qb->andWhere('i.id <> :excludeId')->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
