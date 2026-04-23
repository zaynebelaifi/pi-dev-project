<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    public function invalidateActiveTokensForUser(User $user): int
    {
        return $this->createQueryBuilder('t')
            ->update()
            ->set('t.used_at', ':usedAt')
            ->where('t.user = :user')
            ->andWhere('t.used_at IS NULL')
            ->setParameter('user', $user)
            ->setParameter('usedAt', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    public function findActiveByTokenHash(string $tokenHash): ?PasswordResetToken
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.token_hash = :tokenHash')
            ->andWhere('t.used_at IS NULL')
            ->andWhere('t.expires_at > :now')
            ->setParameter('tokenHash', $tokenHash)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
