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
    public function findByDishWithIngredient(Dish $dish): array
    {
        return $this->createQueryBuilder('di')
            ->innerJoin('di.ingredient', 'i')
            ->addSelect('i')
            ->andWhere('di.dish = :dish')
            ->setParameter('dish', $dish)
            ->orderBy('i.name', 'ASC')
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
