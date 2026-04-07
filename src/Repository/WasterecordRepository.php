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
    public function findForAdminList(?string $search = null): array
    {
        $qb = $this->createQueryBuilder('w')
            ->leftJoin('w.ingredient', 'i')
            ->addSelect('i');

        if (null !== $search && '' !== trim($search)) {
            $qb
                ->andWhere('LOWER(i.name) LIKE :q OR LOWER(w.reason) LIKE :q OR LOWER(w.wasteType) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower(trim($search)).'%');
        }

        return $qb
            ->orderBy('w.date', 'DESC')
            ->addOrderBy('w.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function totalWastedQuantity(): float
    {
        return (float) $this->createQueryBuilder('w')
            ->select('COALESCE(SUM(w.quantityWasted), 0)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
