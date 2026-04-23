<?php

namespace App\Repository;

use App\Entity\FoodDonationEvent;
use App\Entity\EventRegistration;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventRegistration>
 */
class EventRegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventRegistration::class);
    }

    /**
     * @param int[] $eventIds
     * @return array<int, int>
     */
    public function countByEventIds(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.event) AS eventId, COUNT(r.id) AS registrations')
            ->andWhere('r.event IN (:eventIds)')
            ->setParameter('eventIds', $eventIds)
            ->groupBy('r.event')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['eventId']] = (int) $row['registrations'];
        }

        return $result;
    }

    /**
     * @param int[] $eventIds
     * @return int[]
     */
    public function findRegisteredEventIdsForUserId(int $userId, array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.event) AS eventId')
            ->andWhere('r.user = :userId')
            ->andWhere('r.event IN (:eventIds)')
            ->setParameter('userId', $userId)
            ->setParameter('eventIds', $eventIds)
            ->getQuery()
            ->getArrayResult();

        return array_values(array_map(static fn (array $row): int => (int) $row['eventId'], $rows));
    }

    public function isUserRegisteredForEvent(int $userId, int $eventId): bool
    {
        $row = $this->createQueryBuilder('r')
            ->select('r.id')
            ->andWhere('r.user = :userId')
            ->andWhere('r.event = :eventId')
            ->setParameter('userId', $userId)
            ->setParameter('eventId', $eventId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $row !== null;
    }

    public function countForUserId(int $userId): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->innerJoin('r.event', 'e')
            ->andWhere('r.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getRecommendationFingerprintForUserId(int $userId): string
    {
        $row = $this->createQueryBuilder('r')
            ->select('COUNT(r.id) AS totalRegistrations, MAX(r.created_at) AS latestRegistrationAt')
            ->andWhere('r.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleResult();

        $count = (int) ($row['totalRegistrations'] ?? 0);
        $latest = (string) ($row['latestRegistrationAt'] ?? 'none');

        return sprintf('%d|%s', $count, $latest);
    }

    /**
     * @return EventRegistration[]
     */
    public function findForUserId(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.event', 'e')
            ->addSelect('e')
            ->andWhere('r.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('e.event_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findRegisteredUsersForEventId(int $eventId): array
    {
        $rows = $this->createQueryBuilder('r')
            ->innerJoin('r.user', 'u')
            ->addSelect('u')
            ->andWhere('r.event = :eventId')
            ->setParameter('eventId', $eventId)
            ->getQuery()
            ->getResult();

        $users = [];
        foreach ($rows as $registration) {
            if ($registration instanceof EventRegistration && $registration->getUser() instanceof User) {
                $users[] = $registration->getUser();
            }
        }

        return $users;
    }
}
