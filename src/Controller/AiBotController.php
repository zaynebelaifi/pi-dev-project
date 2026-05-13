<?php

namespace App\Controller;

use App\Service\CustomerAiBotService;
use App\Service\GoogleTranslateService;
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
        $language = trim((string) ($payload['language'] ?? 'en'));

        if ($question === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Question is required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $rawAnswer = $customerAiBotService->ask($question);

        return new JsonResponse([
            'success' => true,
            'answer' => $rawAnswer,
            'raw_answer' => $rawAnswer,
            'language' => $language !== '' ? $language : 'en',
        ]);
    }

    #[Route('/translate', name: 'app_ai_bot_translate', methods: ['POST'])]
    public function translate(Request $request, GoogleTranslateService $googleTranslateService): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true);
        $text = trim((string) ($payload['text'] ?? ''));
        $language = trim((string) ($payload['language'] ?? 'en'));

        if ($text === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Text is required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'success' => true,
            'translation' => $googleTranslateService->translate($text, $language),
            'language' => $language !== '' ? $language : 'en',
        ]);
    }
}
