<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[]
     */
    public function findByNormalizedEmail(string $email): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('LOWER(u.email) = :email')
            ->setParameter('email', strtolower(trim($email)))
            ->orderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findUsersWithPhoneNumber(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere("(u.phone_number IS NOT NULL AND TRIM(u.phone_number) != '') OR (u.phone IS NOT NULL AND TRIM(u.phone) != '')")
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findOneByPhoneLoose(?string $phone): ?User
    {
        $normalized = preg_replace('/\D+/', '', (string) $phone);
        if (!$normalized) {
            return null;
        }

        $candidates = array_values(array_unique(array_filter([
            $normalized,
            '+' . $normalized,
            str_starts_with($normalized, '216') ? substr($normalized, 3) : ('216' . $normalized),
            str_starts_with($normalized, '216') ? ('+' . substr($normalized, 3)) : ('+216' . $normalized),
        ])));

        if ($candidates === []) {
            return null;
        }

        return $this->createQueryBuilder('u')
            ->andWhere('u.phone IN (:phones)')
            ->setParameter('phones', $candidates)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
