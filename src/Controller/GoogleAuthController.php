<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class GoogleAuthController extends AbstractController
{
    #[Route('/connect/google', name: 'app_google_connect', methods: ['GET'])]
    public function connect(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['openid', 'email', 'profile'], [
                'prompt' => 'select_account',
            ]);
    }

    #[Route('/connect/google/check', name: 'app_google_check', methods: ['GET'])]
    public function check(
        ClientRegistry $clientRegistry,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SessionInterface $session,
    ): Response {
        try {
            $client = $clientRegistry->getClient('google');
            $accessToken = $client->getAccessToken();
            $googleUser = $client->fetchUserFromToken($accessToken);
        } catch (\Throwable) {
            $this->addFlash('error', 'Google sign in failed. Please try again.');

            return $this->redirectToRoute('app_login');
        }

        if (!$googleUser instanceof GoogleUser) {
            $this->addFlash('error', 'Google did not return a usable account profile.');

            return $this->redirectToRoute('app_login');
        }

        $email = strtolower(trim((string) $googleUser->getEmail()));
        if ($email === '' || !$googleUser->isEmailTrustworthy()) {
            $this->addFlash('error', 'Google did not provide a verified email address.');

            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->findOneByNormalizedEmail($email);
        if (!$user instanceof User) {
            $user = $this->createGoogleUser($googleUser, $email, $passwordHasher);
            $entityManager->persist($user);
            $entityManager->flush();
        }

        if ($user->isBanned()) {
            $this->addFlash('error', 'This account is disabled.');

            return $this->redirectToRoute('app_login');
        }

        $this->populateSession($session, $user);
        $normalizedRole = $this->normalizeRole($user->getRole());

        if ($normalizedRole === 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        if ($normalizedRole === 'ROLE_DELIVERY_MAN') {
            return $this->redirectToRoute('app_driver_deliveries');
        }

        return $this->redirectToRoute('app_home');
    }

    private function createGoogleUser(GoogleUser $googleUser, string $email, UserPasswordHasherInterface $passwordHasher): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($googleUser->getFirstName());
        $user->setLastName($googleUser->getLastName());
        $user->setRole('ROLE_CLIENT');
        $user->setPassword($passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));

        return $user;
    }

    private function populateSession(SessionInterface $session, User $user): void
    {
        $normalizedRole = $this->normalizeRole($user->getRole());
        $displayName = trim((string) $user->getFirstName() . ' ' . (string) $user->getLastName());

        $session->set('user_id', $user->getId());
        $session->set('user_email', $user->getEmail());
        $session->set('user_name', $displayName);
        $session->set('user_role', $normalizedRole);

        if ($normalizedRole === 'ROLE_DELIVERY_MAN') {
            $session->set('delivery_man_id', $user->getReference_id());
        }

        if ($normalizedRole === 'ROLE_CLIENT') {
            $session->set('client_phone', $this->normalizePhone($user->getPhone()));
            $session->set('client_name', $displayName);
        }
    }

    private function normalizeRole(?string $role): string
    {
        $upper = strtoupper(trim((string) $role));

        return match ($upper) {
            'ROLE_ADMIN', 'ADMIN' => 'ROLE_ADMIN',
            'ROLE_CLIENT', 'CLIENT' => 'ROLE_CLIENT',
            'ROLE_DELIVERY_MAN', 'DELIVERY_MAN', 'DELIVERY' => 'ROLE_DELIVERY_MAN',
            default => 'ROLE_CLIENT',
        };
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $normalized = preg_replace('/[^0-9+]/', '', $phone);

        return $normalized === false ? null : $normalized;
    }
}
