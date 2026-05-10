<?php

namespace App\Repository;

use App\Entity\FoodDonationEvent;
use App\Entity\Rating;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rating>
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    public function findUserRating(FoodDonationEvent $event, User $user): ?Rating
    {
        return $this->findOneBy([
            'event' => $event,
            'user' => $user,
        ]);
    }

    public function getAverageEventRating(FoodDonationEvent $event): float
    {
        return (float) $this->createQueryBuilder('r')
            ->select('COALESCE(AVG(r.eventRating), 0)')
            ->andWhere('r.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getAverageFoodRating(FoodDonationEvent $event): float
    {
        return (float) $this->createQueryBuilder('r')
            ->select('COALESCE(AVG(r.foodRating), 0)')
            ->andWhere('r.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getRatingCount(FoodDonationEvent $event): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Rating[]
     */
    public function findRatingsWithUsers(FoodDonationEvent $event): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('u')
            ->leftJoin('r.user', 'u')
            ->andWhere('r.event = :event')
            ->setParameter('event', $event)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
