<?php

namespace App\Controller;

use App\Service\CustomerAiBotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/ai-bot')]
final class AiBotController extends AbstractController
{
    #[Route('/ask', name: 'app_ai_bot_ask', methods: ['POST'])]
    public function ask(Request $request, CustomerAiBotService $customerAiBotService): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true);
        $question = trim((string) ($payload['question'] ?? ''));
        $bookingContext = is_array($payload['booking_context'] ?? null) ? $payload['booking_context'] : null;

        if ($question === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Question is required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $clientId = (int) $request->getSession()->get('user_id', 0);
        $answer = $customerAiBotService->ask(
            $question,
            $bookingContext,
            $clientId > 0 ? $clientId : null
        );

        return new JsonResponse([
            'success' => true,
            'answer' => $answer,
        ]);
    }
}
