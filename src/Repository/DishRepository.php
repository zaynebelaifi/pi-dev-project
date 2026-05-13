<?php

namespace App\Repository;

use App\Entity\Dish;
use App\Entity\Menu;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * @param array{budget?: string, category?: string, diet?: string, mood?: string, q?: string} $filters
     */
    public function createPublicMenuQueryBuilder(array $filters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.menu', 'm')
            ->addSelect('m')
            ->andWhere('d.available = :available')
            ->andWhere('m.isActive = :active')
            ->setParameter('available', true)
            ->setParameter('active', true);

        $budget = (string) ($filters['budget'] ?? '');
        if ($budget === 'low') {
            $qb->andWhere('d.base_price <= :maxBudget')->setParameter('maxBudget', 12);
        } elseif ($budget === 'medium') {
            $qb->andWhere('d.base_price BETWEEN :minBudget AND :maxBudget')
                ->setParameter('minBudget', 12)
                ->setParameter('maxBudget', 25);
        } elseif ($budget === 'premium') {
            $qb->andWhere('d.base_price >= :minBudget')->setParameter('minBudget', 25);
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $qb->andWhere('LOWER(m.title) LIKE :category OR LOWER(d.name) LIKE :category OR LOWER(d.description) LIKE :category')
                ->setParameter('category', '%' . mb_strtolower($category) . '%');
        }

        $diet = (string) ($filters['diet'] ?? '');
        if ($diet === 'vegetarian') {
            $qb->andWhere('LOWER(d.name) NOT LIKE :meatChicken')
                ->andWhere('LOWER(d.name) NOT LIKE :meatBeef')
                ->andWhere('LOWER(d.name) NOT LIKE :meatFish')
                ->andWhere('LOWER(d.description) NOT LIKE :meatChicken')
                ->andWhere('LOWER(d.description) NOT LIKE :meatBeef')
                ->andWhere('LOWER(d.description) NOT LIKE :meatFish')
                ->setParameter('meatChicken', '%chicken%')
                ->setParameter('meatBeef', '%beef%')
                ->setParameter('meatFish', '%fish%');
        } elseif ($diet === 'non_vegetarian') {
            $qb->andWhere('LOWER(d.name) LIKE :meat OR LOWER(d.description) LIKE :meat')
                ->setParameter('meat', '%chicken%');
        }

        $mood = (string) ($filters['mood'] ?? '');
        $moodKeywords = [
            'spicy' => ['spicy', 'pepper', 'hot', 'chili'],
            'sweet' => ['sweet', 'cake', 'dessert', 'chocolate', 'pancake'],
            'healthy' => ['salad', 'fresh', 'healthy', 'fruit', 'light'],
            'popular' => ['signature', 'special', 'popular', 'chef', 'big 4'],
        ];

        if (isset($moodKeywords[$mood])) {
            $or = $qb->expr()->orX();
            foreach ($moodKeywords[$mood] as $index => $keyword) {
                $parameter = 'moodKeyword' . $index;
                $or->add('LOWER(d.name) LIKE :' . $parameter);
                $or->add('LOWER(d.description) LIKE :' . $parameter);
                $or->add('LOWER(m.title) LIKE :' . $parameter);
                $qb->setParameter($parameter, '%' . $keyword . '%');
            }
            $qb->andWhere($or);
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $qb->andWhere('LOWER(d.name) LIKE :publicSearch OR LOWER(d.description) LIKE :publicSearch OR LOWER(m.title) LIKE :publicSearch')
                ->setParameter('publicSearch', '%' . mb_strtolower($search) . '%');
        }

        return $qb
            ->orderBy('m.created_at', 'ASC')
            ->addOrderBy('d.name', 'ASC');
    }
}
