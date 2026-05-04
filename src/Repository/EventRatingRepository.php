<?php

namespace App\Repository;

use App\Entity\EventRating;
use App\Entity\FoodDonationEvent;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventRating>
 */
class EventRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventRating::class);
    }

    public function findOneByEventAndUser(FoodDonationEvent $event, User $user): ?EventRating
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.donationEvent = :event')
            ->andWhere('r.user = :user')
            ->setParameter('event', $event)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByDonationEvent(FoodDonationEvent $event): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.donationEvent = :event')
            ->setParameter('event', $event)
            ->orderBy('r.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
