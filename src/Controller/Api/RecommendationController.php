<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Service\EventRecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

final class RecommendationController extends AbstractController
{
    public function __construct(
        private readonly EventRecommendationService $eventRecommendationService,
        private readonly RequestStack $requestStack,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/api/events/recommendations', name: 'api_events_recommendations', methods: ['GET'])]
    public function getRecommendations(): JsonResponse
    {
        $session = $this->requestStack->getSession();
        $sessionUserId = $session->get('user_id');
        $userRole = (string) ($session->get('user_role') ?? '');

        if (!is_numeric($sessionUserId)) {
            return $this->json([
                'success' => false,
                'authenticated' => false,
                'recommendations' => [],
                'total' => 0,
                'message' => 'Login to see recommendations.',
            ]);
        }

        if ($userRole !== 'ROLE_CLIENT') {
            return $this->json([
                'success' => true,
                'authenticated' => true,
                'role' => $userRole,
                'recommendations' => [],
                'total' => 0,
                'message' => 'Recommendations are available for customer accounts only.',
            ]);
        }

        $user = $this->userRepository->find((int) $sessionUserId);
        if ($user === null) {
            return $this->json([
                'success' => false,
                'authenticated' => false,
                'recommendations' => [],
                'total' => 0,
                'message' => 'Session user not found.',
            ]);
        }

        $recommendations = $this->eventRecommendationService->getRecommendations($user);

        foreach ($recommendations as &$recommendation) {
            $eventId = (int) ($recommendation['id'] ?? 0);
            $recommendation['register_url'] = $eventId > 0
                ? $this->generateUrl('api_event_register', ['id' => $eventId])
                : null;
            $recommendation['can_register'] = (($recommendation['status'] ?? '') === 'SCHEDULED');
        }
        unset($recommendation);

        return $this->json([
            'success' => true,
            'authenticated' => true,
            'role' => 'ROLE_CUSTOMER',
            'recommendations' => $recommendations,
            'total' => count($recommendations),
        ]);
    }
}
