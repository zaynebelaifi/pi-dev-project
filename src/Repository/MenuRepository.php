<?php

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Menu>
 */
class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    /**
     * @return Menu[]
     */
    public function findForAdminList(?string $search = null, string $sort = 'created_at', string $dir = 'DESC'): array
    {
        $allowedSorts = ['id', 'title', 'created_at', 'isActive'];
        if (!\in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $direction = \strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('m');

        if (null !== $search && '' !== \trim($search)) {
            $qb
                ->andWhere('LOWER(m.title) LIKE :q OR LOWER(m.description) LIKE :q')
                ->setParameter('q', '%'.\mb_strtolower(\trim($search)).'%');
        }

        return $qb
            ->orderBy('m.'.$sort, $direction)
            ->getQuery()
            ->getResult();
    }
}
