<?php

namespace App\Repository;

use App\Entity\User;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepositoryInterface;
use Webauthn\PublicKeyCredentialUserEntity;

class WebauthnUserEntityRepository implements PublicKeyCredentialUserEntityRepositoryInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function findOneByUsername(string $username): ?PublicKeyCredentialUserEntity
    {
        $email = strtolower(trim($username));
        if ($email === '') {
            return null;
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            $user = $this->userRepository->createQueryBuilder('u')
                ->andWhere('LOWER(u.email) = :email')
                ->setParameter('email', $email)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        if (!$user instanceof User || !$user->getId()) {
            return null;
        }

        $displayName = trim((string) ($user->getFirstName() . ' ' . $user->getLastName()));
        if ($displayName === '') {
            $displayName = (string) $user->getEmail();
        }

        return PublicKeyCredentialUserEntity::create(
            (string) $user->getEmail(),
            (string) $user->getId(),
            $displayName,
            null
        );
    }

    public function findOneByUserHandle(string $userHandle): ?PublicKeyCredentialUserEntity
    {
        if ($userHandle === '' || !ctype_digit($userHandle)) {
            return null;
        }

        $user = $this->userRepository->find((int) $userHandle);
        if (!$user instanceof User || !$user->getId()) {
            return null;
        }

        $displayName = trim((string) ($user->getFirstName() . ' ' . $user->getLastName()));
        if ($displayName === '') {
            $displayName = (string) $user->getEmail();
        }

        return PublicKeyCredentialUserEntity::create(
            (string) $user->getEmail(),
            (string) $user->getId(),
            $displayName,
            null
        );
    }
}
