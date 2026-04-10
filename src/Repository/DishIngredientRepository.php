<?php

namespace App\Repository;

use App\Entity\Dish;
use App\Entity\DishIngredient;
use App\Entity\Ingredient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DishIngredient>
 */
class DishIngredientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DishIngredient::class);
    }

    /**
     * @return DishIngredient[]
     */
    public function findByDishWithIngredient(
        Dish $dish,
        ?string $search = null,
        ?string $status = null,
        string $sort = 'ingredient',
        string $dir = 'ASC',
        ?\DateTimeInterface $today = null
    ): array
    {
        $sortMap = [
            'ingredient' => 'i.name',
            'required' => 'di.quantityRequired',
            'stock' => 'i.quantityInStock',
            'unit' => 'i.unit',
            'expiry' => 'i.expiryDate',
        ];

        $sortField = $sortMap[$sort] ?? 'i.name';
        $direction = \strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $today = $today ?? new \DateTimeImmutable('today');

        $qb = $this->createQueryBuilder('di')
            ->innerJoin('di.ingredient', 'i')
            ->addSelect('i')
            ->andWhere('di.dish = :dish')
            ->setParameter('dish', $dish);

        if (null !== $search && '' !== trim($search)) {
            $qb
                ->andWhere('LOWER(i.name) LIKE :q OR LOWER(i.unit) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower(trim($search)).'%');
        }

        if (null !== $status && '' !== trim($status)) {
            $normalized = mb_strtolower(trim($status));
            if ('ok' === $normalized) {
                $qb
                    ->andWhere('i.quantityInStock >= di.quantityRequired')
                    ->andWhere('(i.expiryDate IS NULL OR i.expiryDate >= :today)')
                    ->setParameter('today', $today->format('Y-m-d'));
            } elseif ('insufficient' === $normalized) {
                $qb
                    ->andWhere('i.quantityInStock < di.quantityRequired')
                    ->andWhere('(i.expiryDate IS NULL OR i.expiryDate >= :today)')
                    ->setParameter('today', $today->format('Y-m-d'));
            } elseif ('expired' === $normalized) {
                $qb
                    ->andWhere('i.expiryDate < :today')
                    ->setParameter('today', $today->format('Y-m-d'));
            }
        }

        return $qb
            ->orderBy($sortField, $direction)
            ->addOrderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByDishAndIngredient(Dish $dish, Ingredient $ingredient): ?DishIngredient
    {
        return $this->findOneBy([
            'dish' => $dish,
            'ingredient' => $ingredient,
        ]);
    }
}
