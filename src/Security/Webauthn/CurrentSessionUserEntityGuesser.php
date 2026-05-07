<?php

namespace App\Security\Webauthn;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Webauthn\Bundle\Security\Guesser\UserEntityGuesser;
use Webauthn\PublicKeyCredentialUserEntity;

class CurrentSessionUserEntityGuesser implements UserEntityGuesser
{
    public function __construct(
        private RequestStack $requestStack,
        private UserRepository $userRepository,
    ) {
    }

    public function findUserEntity(Request $request): PublicKeyCredentialUserEntity
    {
        $session = $this->requestStack->getSession();
        if ($session === null) {
            throw new \RuntimeException('Session is unavailable.');
        }

        $userId = (int) $session->get('user_id', 0);
        if ($userId <= 0) {
            throw new \RuntimeException('User not authenticated for WebAuthn registration.');
        }

        $user = $this->userRepository->find($userId);
        if (!$user instanceof User || !$user->getId()) {
            throw new \RuntimeException('Authenticated user not found.');
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
