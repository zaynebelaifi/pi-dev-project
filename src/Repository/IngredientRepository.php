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
    public function findForAdminList(
        ?string $search = null,
        string $sort = 'name',
        string $dir = 'ASC',
        ?string $stockStatus = null,
        ?string $unit = null,
        ?\DateTimeInterface $today = null
    ): array {
        $allowedSorts = ['name', 'quantityInStock', 'minStockLevel', 'unit', 'unitCost', 'expiryDate', 'createdAt'];
        if (!\in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        $direction = \strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $today = $today ?? new \DateTimeImmutable('today');

        $qb = $this->createQueryBuilder('i');

        if (null !== $search && '' !== trim($search)) {
            $qb
                ->andWhere('LOWER(i.name) LIKE :q OR LOWER(i.unit) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower(trim($search)).'%');
        }

        if (null !== $unit && '' !== trim($unit)) {
            $qb
                ->andWhere('LOWER(i.unit) = :unit')
                ->setParameter('unit', mb_strtolower(trim($unit)));
        }

        if (null !== $stockStatus && '' !== trim($stockStatus)) {
            $normalizedStatus = mb_strtolower(trim($stockStatus));
            if ('low' === $normalizedStatus) {
                $qb->andWhere('i.quantityInStock <= i.minStockLevel');
            } elseif ('expired' === $normalizedStatus) {
                $qb
                    ->andWhere('i.expiryDate < :today')
                    ->setParameter('today', $today->format('Y-m-d'));
            } elseif ('healthy' === $normalizedStatus) {
                $qb
                    ->andWhere('i.quantityInStock > i.minStockLevel')
                    ->andWhere('i.expiryDate >= :today')
                    ->setParameter('today', $today->format('Y-m-d'));
            }
        }

        return $qb
            ->orderBy('i.'.$sort, $direction)
            ->addOrderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return string[]
     */
    public function findDistinctUnits(): array
    {
        $rows = $this->createQueryBuilder('i')
            ->select('DISTINCT i.unit AS unit')
            ->where('i.unit IS NOT NULL')
            ->andWhere('TRIM(i.unit) <> :empty')
            ->setParameter('empty', '')
            ->orderBy('i.unit', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(static fn (array $row): string => (string) ($row['unit'] ?? ''), $rows)));
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
