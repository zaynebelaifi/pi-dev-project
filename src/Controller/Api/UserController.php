<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    #[Route('/api/user/role', name: 'api_user_role', methods: ['GET'])]
    public function getUserRole(): JsonResponse
    {
        $session = $this->requestStack->getSession();
        $sessionUserId = $session->get('user_id');

        if (!is_numeric($sessionUserId)) {
            return $this->json([
                'authenticated' => false,
                'role' => null,
            ]);
        }

        $role = (string) ($session->get('user_role') ?? 'ROLE_CLIENT');

        if ($role === 'ROLE_ADMIN') {
            $normalizedRole = 'ROLE_ADMIN';
        } elseif ($role === 'ROLE_DELIVERY_MAN') {
            $normalizedRole = 'ROLE_DELIVERY';
        } else {
            $normalizedRole = 'ROLE_CUSTOMER';
        }

        return $this->json([
            'authenticated' => true,
            'role' => $normalizedRole,
        ]);
    }
}
