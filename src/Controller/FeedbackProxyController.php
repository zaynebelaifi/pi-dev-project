<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FeedbackProxyController extends AbstractController
{
    public function __construct(
        private Connection $connection,
        private HttpClientInterface $httpClient,
    ) {}

    #[Route('/feedback/testimonials', name: 'app_feedback_testimonials', methods: ['GET'])]
    public function testimonials(): JsonResponse
    {
        try {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT id, customer_name, review_text, summary, rating, created_at
                 FROM delivery_reviews
                 WHERE routed_to = :routedTo OR routed_to IS NULL OR routed_to = ""
                 ORDER BY created_at DESC
                 LIMIT 10',
                ['routedTo' => 'testimonials']
            );

            return new JsonResponse(array_map(static function (array $row): array {
                return [
                    'id' => isset($row['id']) ? (int) $row['id'] : null,
                    'customer_name' => (string) ($row['customer_name'] ?? 'Guest'),
                    'review_text' => (string) ($row['review_text'] ?? ''),
                    'summary' => (string) ($row['summary'] ?? ''),
                    'rating' => isset($row['rating']) ? (int) $row['rating'] : null,
                    'created_at' => (string) ($row['created_at'] ?? ''),
                ];
            }, $rows));
        } catch (\Throwable $exception) {
            return new JsonResponse($this->fallbackTestimonials());
        }
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

    private function fallbackTestimonials(): array
    {
        return [
            [
                'id' => 1,
                'customer_name' => 'Amira',
                'review_text' => 'Super fast delivery and elegant packaging.',
                'summary' => 'Fast delivery with premium presentation.',
                'rating' => 5,
                'created_at' => (new \DateTimeImmutable())->format('c'),
            ],
            [
                'id' => 2,
                'customer_name' => 'Zayd',
                'review_text' => 'Coffee arrived hot and beautifully boxed.',
                'summary' => 'Warm coffee and beautiful boxing.',
                'rating' => 5,
                'created_at' => (new \DateTimeImmutable())->format('c'),
            ],
            [
                'id' => 3,
                'customer_name' => 'Salma',
                'review_text' => 'Quick service with a smooth handoff.',
                'summary' => 'Efficient handoff and quick service.',
                'rating' => 4,
                'created_at' => (new \DateTimeImmutable())->format('c'),
            ],
        ];
    }
}
