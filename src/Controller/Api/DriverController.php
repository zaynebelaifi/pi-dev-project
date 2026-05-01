<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class DriverController extends AbstractController
{
    #[Route('/api/driver/me', name: 'api_driver_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if ($user === null) {
            return new JsonResponse(['message' => 'Unauthorized.'], 401);
        }

        return new JsonResponse([
            'id' => method_exists($user, 'getId') ? $user->getId() : null,
            'email' => method_exists($user, 'getEmail') ? $user->getEmail() : null,
            'roles' => method_exists($user, 'getRoles') ? $user->getRoles() : [],
        ]);
    }
}