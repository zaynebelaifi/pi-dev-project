<?php

namespace App\Repository;

use App\Entity\Wasterecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Wasterecord>
 */
class WasterecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Wasterecord::class);
    }

    /**
     * @return Wasterecord[]
     */
    public function findForAdminList(
        ?string $search = null,
        string $sort = 'date',
        string $dir = 'DESC',
        ?string $wasteType = null,
        ?\DateTimeInterface $dateFrom = null,
        ?\DateTimeInterface $dateTo = null
    ): array {
        $allowedSorts = [
            'date' => 'w.date',
            'ingredient' => 'i.name',
            'quantityWasted' => 'w.quantityWasted',
            'wasteType' => 'w.wasteType',
            'reason' => 'w.reason',
            'id' => 'w.id',
        ];

        $sortField = $allowedSorts[$sort] ?? 'w.date';
        $direction = \strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('w')
            ->leftJoin('w.ingredient', 'i')
            ->addSelect('i');

        if (null !== $search && '' !== trim($search)) {
            $qb
                ->andWhere('LOWER(i.name) LIKE :q OR LOWER(w.reason) LIKE :q OR LOWER(w.wasteType) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower(trim($search)).'%');
        }

        if (null !== $wasteType && '' !== trim($wasteType)) {
            $qb
                ->andWhere('LOWER(w.wasteType) = :wasteType')
                ->setParameter('wasteType', mb_strtolower(trim($wasteType)));
        }

        if ($dateFrom instanceof \DateTimeInterface) {
            $qb
                ->andWhere('w.date >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom->format('Y-m-d'));
        }

        if ($dateTo instanceof \DateTimeInterface) {
            $qb
                ->andWhere('w.date <= :dateTo')
                ->setParameter('dateTo', $dateTo->format('Y-m-d'));
        }

        return $qb
            ->orderBy($sortField, $direction)
            ->addOrderBy('w.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return string[]
     */
    public function findDistinctWasteTypes(): array
    {
        $rows = $this->createQueryBuilder('w')
            ->select('DISTINCT w.wasteType AS wasteType')
            ->where('w.wasteType IS NOT NULL')
            ->andWhere('TRIM(w.wasteType) <> :empty')
            ->setParameter('empty', '')
            ->orderBy('w.wasteType', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(static fn (array $row): string => (string) ($row['wasteType'] ?? ''), $rows)));
    }

    public function totalWastedQuantity(): float
    {
        return (float) $this->createQueryBuilder('w')
            ->select('COALESCE(SUM(w.quantityWasted), 0)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
