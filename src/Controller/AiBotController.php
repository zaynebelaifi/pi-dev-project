<?php

namespace App\Controller;

use App\Service\CustomerAiBotService;
use App\Service\OpenWeatherMapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/ai-bot')]
final class AiBotController extends AbstractController
{
    #[Route('/ask', name: 'app_ai_bot_ask', methods: ['POST'])]
    public function ask(
        Request $request,
        CustomerAiBotService $customerAiBotService,
        OpenWeatherMapService $openWeatherMapService,
    ): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true);
        $question = trim((string) ($payload['question'] ?? ''));
        $menuMatches = is_array($payload['menuMatches'] ?? null) ? $payload['menuMatches'] : [];

        if ($question === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Question is required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $weather = $openWeatherMapService->getCurrentByCity('Tunis');
        $answer = $customerAiBotService->ask($question, [
            'weather' => $weather,
            'menu_matches' => $menuMatches,
        ]);

        return new JsonResponse([
            'success' => true,
            'answer' => $answer,
            'weather' => $weather,
        ]);
    }
}
