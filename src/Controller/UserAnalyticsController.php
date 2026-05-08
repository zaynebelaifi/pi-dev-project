<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\UserAnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * UserAnalyticsController — JSON API powering the Advanced User Analytics panel.
 *
 * All 4 endpoints use proper dependency injection (no $this->container->get()),
 * return { status, data, message } JSON, and guard for admin session.
 */
#[Route('/api/users')]
class UserAnalyticsController extends AbstractController
{
    public function __construct(
        private readonly UserAnalyticsService $analytics,
        private readonly UserRepository $userRepository,
    ) {}

    // ── FEATURE 1 — GET /api/users/{id}/stats ──────────────────────────

    #[Route('/{id}/stats', name: 'api_user_stats', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function stats(int $id, Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return $this->errorJson('Unauthorized — admin access required.', 403);
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->errorJson("User #{$id} not found.", 404);
        }

        return $this->json([
            'status'  => 'success',
            'data'    => $this->analytics->getUserStats($user),
            'message' => 'Statistics retrieved.',
        ]);
    }

    // ── FEATURE 2 — GET /api/users/inactive ────────────────────────────
    // NOTE: Must be declared BEFORE /{id} routes to avoid routing conflicts.

    #[Route('/inactive', name: 'api_user_inactive', methods: ['GET'])]
    public function inactiveUsers(Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return $this->errorJson('Unauthorized — admin access required.', 403);
        }

        $data = $this->analytics->getInactiveUsers();

        return $this->json([
            'status'  => 'success',
            'data'    => $data,
            'count'   => count($data),
            'message' => count($data) > 0
                ? sprintf('%d inactive user(s) found.', count($data))
                : 'All users are currently active!',
        ]);
    }

    // ── FEATURE 3 — GET /api/users/{id}/loyalty-score ──────────────────

    #[Route('/{id}/loyalty-score', name: 'api_user_loyalty', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function loyaltyScore(int $id, Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return $this->errorJson('Unauthorized — admin access required.', 403);
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->errorJson("User #{$id} not found.", 404);
        }

        return $this->json([
            'status'  => 'success',
            'data'    => $this->analytics->calculateLoyaltyScore($user),
            'message' => 'Loyalty score calculated.',
        ]);
    }

    // ── FEATURE 4 — GET /api/users/{id}/recommendation ─────────────────

    #[Route('/{id}/recommendation', name: 'api_user_recommendation', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function recommendation(int $id, Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return $this->errorJson('Unauthorized — admin access required.', 403);
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->errorJson("User #{$id} not found.", 404);
        }

        return $this->json([
            'status'  => 'success',
            'data'    => $this->analytics->getAIRecommendation($user),
            'message' => 'AI recommendations generated.',
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function isAdmin(Request $request): bool
    {
        return $request->getSession()->get('user_role') === 'ROLE_ADMIN';
    }

    private function errorJson(string $message, int $status = 400): JsonResponse
    {
        return new JsonResponse([
            'status'  => 'error',
            'data'    => null,
            'message' => $message,
        ], $status);
    }
}
