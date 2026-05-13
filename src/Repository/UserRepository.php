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
    /** @var string[] */
    private const CUSTOMER_ROLES = ['role_client', 'role_customer'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[]
     */
    public function findByNormalizedEmail(string $email): array
    {
        $normalizedEmail = strtolower(trim($email));

        try {
            return $this->createQueryBuilder('u')
                ->andWhere('LOWER(u.email) = :email')
                ->setParameter('email', $normalizedEmail)
                ->orderBy('u.id', 'DESC')
                ->getQuery()
                ->getResult();
        } catch (\Throwable $exception) {
            return $this->findLegacyUsersByNormalizedEmail($normalizedEmail);
        }
    }

    public function findOneByNormalizedEmail(string $email): ?User
    {
        return $this->findByNormalizedEmail($email)[0] ?? null;
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

    /**
     * @return User[]
     */
    public function findCustomerUsersWithPhoneNumber(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('LOWER(u.role) IN (:customerRoles)')
            ->andWhere("(u.phone_number IS NOT NULL AND TRIM(u.phone_number) != '') OR (u.phone IS NOT NULL AND TRIM(u.phone) != '')")
            ->setParameter('customerRoles', self::CUSTOMER_ROLES)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

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
            ->andWhere('(u.phone IN (:phones) OR u.phone_number IN (:phones))')
            ->setParameter('phones', $candidates)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return User[]
     */
    private function findLegacyUsersByNormalizedEmail(string $email): array
    {
        try {
            $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
                'SELECT id, email, password, role FROM `user1` WHERE LOWER(email) = :email ORDER BY id DESC',
                ['email' => $email]
            );
        } catch (\Throwable $exception) {
            return [];
        }

        $users = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $user = new User();
            $user->setId((int) ($row['id'] ?? 0));
            $user->setEmail((string) ($row['email'] ?? ''));
            $user->setPassword((string) ($row['password'] ?? ''));
            $user->setRole((string) ($row['role'] ?? 'ROLE_CLIENT'));
            $users[] = $user;
        }

        return $users;
    }
}
