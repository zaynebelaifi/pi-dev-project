<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\UserInsightsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/user/insights', name: 'app_user_insights', methods: ['GET'])]
    public function insights(UserRepository $userRepository, Request $request, UserInsightsService $insightsService): Response
    {
        $session = $request->getSession();
        $sessionUserId = (int) $session->get('user_id', 0);

        // Prefer a client profile for insights so metrics are meaningful for orders/loyalty.
        $defaultUserId = $insightsService->getDefaultInsightsUserId();

        if ($defaultUserId === null && $sessionUserId > 0) {
            $defaultUserId = $sessionUserId;
        }

        if ($defaultUserId === null) {
            $firstUser = $userRepository->createQueryBuilder('u')
                ->select('u.id')
                ->orderBy('u.id', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if (is_array($firstUser) && isset($firstUser['id'])) {
                $defaultUserId = (int) $firstUser['id'];
            }
        }

        return $this->render('user/insights.html.twig', [
            'defaultUserId' => $defaultUserId,
        ]);
    }

    #[Route('/user/insights-api/default-target', name: 'api_default_insights_target', methods: ['GET'])]
    public function defaultInsightsTarget(UserInsightsService $insightsService): JsonResponse
    {
        $target = $insightsService->getDefaultInsightsTarget();
        if ($target === null) {
            return $this->json([
                'success' => false,
                'message' => 'No suitable user found for insights.',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => $target,
        ]);
    }

    #[Route('/user/insights-api/{id}/stats', name: 'api_user_stats', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function stats(int $id, UserInsightsService $insightsService): JsonResponse
    {
        $stats = $insightsService->getUserStats($id);
        if ($stats === null) {
            return $this->json([
                'success' => false,
                'message' => 'User not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    #[Route('/user/insights-api/inactive', name: 'api_users_inactive', methods: ['GET'])]
    public function inactiveUsers(UserInsightsService $insightsService): JsonResponse
    {
        $inactiveUsers = $insightsService->getInactiveUsers(30);

        return $this->json([
            'success' => true,
            'data' => [
                'thresholdDays' => 30,
                'count' => count($inactiveUsers),
                'users' => $inactiveUsers,
            ],
        ]);
    }

    #[Route('/user/insights-api/{id}/loyalty-score', name: 'api_user_loyalty_score', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function loyaltyScore(int $id, UserInsightsService $insightsService): JsonResponse
    {
        $score = $insightsService->getLoyaltyScore($id);
        if ($score === null) {
            return $this->json([
                'success' => false,
                'message' => 'User not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => $score,
        ]);
    }

    #[Route('/user/insights-api/{id}/recommendation', name: 'api_user_recommendation', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function recommendation(int $id, UserInsightsService $insightsService): JsonResponse
    {
        $recommendation = $insightsService->getRecommendation($id);
        if ($recommendation === null) {
            return $this->json([
                'success' => false,
                'message' => 'User not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => $recommendation,
        ]);
    }

    #[Route('/user/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/user/{id}', name: 'app_user_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/user/{id}', name: 'app_user_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
