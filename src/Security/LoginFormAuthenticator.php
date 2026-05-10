<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AuthSessionService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class LoginFormAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private AuthSessionService $authSessionService,
        private UserPasswordHasherInterface $passwordHasher,
        private LoggerInterface $logger,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST') && $request->getPathInfo() === '/login';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $loginPayload = (array) $request->request->all('login');
        $email = strtolower(trim((string) ($loginPayload['email'] ?? '')));
        $password = (string) ($loginPayload['password'] ?? '');
        $csrfToken = (string) ($loginPayload['_token'] ?? '');

        $request->getSession()->set('last_login_email', $email);

        return new SelfValidatingPassport(
            new UserBadge($email, function (string $identifier) use ($password): User {
                return $this->resolveUserForLogin($identifier, $password);
            }),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        }

        // Keep legacy session keys populated so existing role/session checks stay operational.
        $this->authSessionService->populateSession($request->getSession(), $user);

        $normalizedRole = $this->authSessionService->normalizeRole($user->getRole());

        if ($normalizedRole !== (string) $user->getRole() && $this->entityManager->isOpen()) {
            $user->setRole($normalizedRole);
            $this->entityManager->flush();
        }

        $this->logger->info('Login success.', [
            'userId' => $user->getId(),
            'role' => $normalizedRole,
            'ip' => $request->getClientIp(),
        ]);

        if ($normalizedRole === 'ROLE_ADMIN') {
            return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
        }

        if ($normalizedRole === 'ROLE_DELIVERY_MAN') {
            return new RedirectResponse($this->urlGenerator->generate('app_driver_deliveries'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->warning('Login failure.', [
            'email' => (string) $request->getSession()->get('last_login_email', ''),
            'reason' => $exception->getMessageKey(),
            'ip' => $request->getClientIp(),
        ]);

        $session = $request->getSession();
        if ($session !== null) {
            $session->set('auth_login_error', 'Invalid email or password.');
        }

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    private function resolveUserForLogin(string $identifier, string $plainPassword): User
    {
        if ($plainPassword === '') {
            throw new CustomUserMessageAuthenticationException('Invalid credentials.');
        }

        $candidates = $this->userRepository->findByNormalizedEmail($identifier);
        foreach ($candidates as $candidate) {
            if (!$candidate instanceof User) {
                continue;
            }

            if ($this->passwordHasher->isPasswordValid($candidate, $plainPassword)) {
                return $candidate;
            }

            if ($this->isLegacyPasswordValid($candidate, $plainPassword)) {
                $this->upgradeLegacyPasswordHash($candidate, $plainPassword);

                return $candidate;
            }
        }

        throw new CustomUserMessageAuthenticationException('Invalid credentials.');
    }

    private function isLegacyPasswordValid(User $user, string $plainPassword): bool
    {
        $stored = (string) ($user->getPassword() ?? '');
        if ($stored === '' || $this->looksLikeSymfonyPasswordHash($stored)) {
            return false;
        }

        $legacyHash = base64_encode(hash('sha256', $plainPassword, true));

        return hash_equals($stored, $legacyHash);
    }

    private function upgradeLegacyPasswordHash(User $user, string $plainPassword): void
    {
        if (!$user instanceof PasswordAuthenticatedUserInterface || !$this->entityManager->isOpen()) {
            return;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $this->entityManager->flush();
    }

    private function looksLikeSymfonyPasswordHash(string $value): bool
    {
        return str_starts_with($value, '$2y$')
            || str_starts_with($value, '$argon2i$')
            || str_starts_with($value, '$argon2id$');
    }
}
