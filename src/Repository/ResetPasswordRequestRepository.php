<?php

namespace App\Repository;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ResetPasswordRequest>
 */
class ResetPasswordRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordRequest::class);
    }

    /**
     * Finds a non-expired reset request by its hashed token.
     * Returns null if not found or already expired.
     */
    public function findValidRequest(string $hashedToken): ?ResetPasswordRequest
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.hashedToken = :token')
            ->andWhere('r.expiresAt > :now')
            ->setParameter('token', $hashedToken)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Removes all existing reset requests for a given user.
     * Called before creating a new request to prevent token accumulation.
     */
    public function removeAllForUser(User $user): void
    {
        $this->createQueryBuilder('r')
            ->delete()
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Removes all expired tokens (useful for a scheduled cleanup command).
     */
    public function removeExpiredRequests(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->delete()
            ->andWhere('r.expiresAt <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
