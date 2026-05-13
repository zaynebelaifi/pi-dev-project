<?php

namespace App\Repository;

use App\Entity\PasswordResetCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetCode>
 */
class PasswordResetCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetCode::class);
    }

    public function findLatestUsableForEmail(string $email): ?PasswordResetCode
    {
        return $this->createQueryBuilder('p')
            ->andWhere('LOWER(p.email) = :email')
            ->andWhere('p.usedAt IS NULL')
            ->setParameter('email', strtolower(trim($email)))
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countRecentForEmail(string $email, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('LOWER(p.email) = :email')
            ->andWhere('p.createdAt >= :since')
            ->setParameter('email', strtolower(trim($email)))
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
