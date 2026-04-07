<?php

namespace App\Repository;

use App\Entity\Dish;
use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dish>
 */
class DishRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dish::class);
    }

    /**
     * @return Dish[]
     */
    public function findForAdminList(?string $search = null, string $sort = 'created_at', string $dir = 'DESC', ?Menu $menu = null): array
    {
        $allowedSorts = ['id', 'name', 'base_price', 'stock_quantity', 'created_at', 'available'];
        if (!\in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $direction = \strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.menu', 'm')
            ->addSelect('m');

        if (null !== $menu) {
            $qb->andWhere('d.menu = :menu')->setParameter('menu', $menu);
        }

        if (null !== $search && '' !== \trim($search)) {
            $q = '%'.\mb_strtolower(\trim($search)).'%';
            $qb
                ->andWhere('LOWER(d.name) LIKE :q OR LOWER(d.description) LIKE :q OR LOWER(m.title) LIKE :q')
                ->setParameter('q', $q);
        }

        return $qb
            ->orderBy('d.'.$sort, $direction)
            ->getQuery()
            ->getResult();
    }
}
