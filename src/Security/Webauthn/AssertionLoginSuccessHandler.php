<?php

namespace App\Security\Webauthn;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use App\Service\AuthSessionService;
use App\Repository\WebauthnCredentialDoctrineRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Webauthn\Bundle\Security\Handler\SuccessHandler;

class AssertionLoginSuccessHandler implements SuccessHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private WebauthnCredentialDoctrineRepository $credentialDoctrineRepository,
        private RequestStack $requestStack,
        private AuthSessionService $authSessionService,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function onSuccess(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse([
                'status' => 'error',
                'errorMessage' => 'Invalid assertion payload.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $rawId = (string) ($payload['rawId'] ?? '');
        $credentialIdBinary = $this->base64UrlDecode($rawId);
        if ($credentialIdBinary === null) {
            return new JsonResponse([
                'status' => 'error',
                'errorMessage' => 'Invalid credential identifier.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $credentialId = base64_encode($credentialIdBinary);
        $record = $this->credentialDoctrineRepository->findOneBy(['credential_id' => $credentialId]);

        if (!$record instanceof WebauthnCredential || !$record->getUserHandle() || !ctype_digit($record->getUserHandle())) {
            return new JsonResponse([
                'status' => 'error',
                'errorMessage' => 'Unknown credential owner.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->userRepository->find((int) $record->getUserHandle());
        if (!$user instanceof User || $user->isBanned()) {
            return new JsonResponse([
                'status' => 'error',
                'errorMessage' => 'User not allowed.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $session = $this->requestStack->getSession();
        if ($session === null) {
            return new JsonResponse([
                'status' => 'error',
                'errorMessage' => 'Session not available.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Create a first-class Symfony authenticated token so protected routes
        // (e.g. /dashboard) work identically for password and WebAuthn logins.
        $this->tokenStorage->setToken(new PostAuthenticationToken($user, 'main', $user->getRoles()));
        $this->authSessionService->populateSession($session, $user);
        $normalizedRole = $this->authSessionService->normalizeRole($user->getRole());

        $redirect = '/';
        if ($normalizedRole === 'ROLE_ADMIN') {
            $redirect = '/admin';
        } elseif ($normalizedRole === 'ROLE_DELIVERY_MAN') {
            $redirect = '/driver/deliveries';
        }

        return new JsonResponse([
            'status' => 'ok',
            'errorMessage' => '',
            'redirect' => $redirect,
        ], Response::HTTP_CREATED);
    }

    private function base64UrlDecode(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $padded = strtr($value, '-_', '+/');
        $padding = strlen($padded) % 4;
        if ($padding > 0) {
            $padded .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($padded, true);

        return is_string($decoded) ? $decoded : null;
    }

    private function normalizeRole(?string $role): string
    {
        $upper = strtoupper(trim((string) $role));

        return match ($upper) {
            'ROLE_ADMIN', 'ADMIN' => 'ROLE_ADMIN',
            'ROLE_DELIVERY_MAN', 'DELIVERY_MAN', 'DELIVERY' => 'ROLE_DELIVERY_MAN',
            default => 'ROLE_CLIENT',
        };
    }

}
