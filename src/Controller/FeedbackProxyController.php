<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FeedbackProxyController extends AbstractController
{
    public function __construct(private HttpClientInterface $httpClient) {}

    #[Route('/feedback/testimonials', name: 'app_feedback_testimonials', methods: ['GET'])]
    public function testimonials(): JsonResponse
    {
        return $this->proxy('/testimonials');
    }

    #[Route('/feedback/support-queue', name: 'app_feedback_support_queue', methods: ['GET'])]
    public function supportQueue(): JsonResponse
    {
        return $this->proxy('/support/queue');
    }

    private function proxy(string $path): JsonResponse
    {
        $baseUrl = rtrim((string) ($_ENV['FEEDBACK_AI_BASE_URL'] ?? 'http://127.0.0.1:8001'), '/');

        try {
            $response = $this->httpClient->request('GET', $baseUrl . $path, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);
            $data = json_decode($content, true);

            if (!is_array($data)) {
                $data = [];
            }

            return new JsonResponse($data, $statusCode);
        } catch (\Throwable $exception) {
            return new JsonResponse([], Response::HTTP_BAD_GATEWAY);
        }
    }
}
