<?php

namespace App\Controller;

use App\Entity\FoodDonationEvent;
use App\Repository\FoodDonationEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/admin/food-donation/stats')]
final class FoodDonationStatsController extends AbstractController
{
    #[Route(name: 'app_food_donation_stats', methods: ['GET'])]
    public function index(Request $request, FoodDonationEventRepository $eventRepository): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        return $this->render('admin/food_donation_stats/index.html.twig', [
            'activeRoute' => 'app_food_donation_stats',
        ]);
    }

    #[Route('/dashboard', name: 'app_food_donation_stats_dashboard', methods: ['GET'])]
    public function dashboard(Request $request, FoodDonationEventRepository $eventRepository): Response
    {
        if ($redirect = $this->denyUnlessAdmin($request)) {
            return $redirect;
        }

        $events = $eventRepository->findAll();

        $chartData = [
            'events' => $this->formatEventsForCharts($events),
            'stats' => $this->calculateStats($events),
        ];

        $charityColorMap = $this->generateCharityColorMap($events);

        return $this->render('admin/food_donation_stats/dashboard.html.twig', [
            'activeRoute' => 'app_food_donation_stats_dashboard',
            'chartData' => json_encode($chartData),
            'charityColorMap' => json_encode($charityColorMap),
            'stats' => $chartData['stats'],
            'events' => $events,
        ]);
    }

    #[Route('/ai-report', name: 'app_food_donation_ai_report', methods: ['POST'])]
    public function aiReport(Request $request, HttpClientInterface $httpClient, FoodDonationEventRepository $eventRepository): JsonResponse
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // Symfony loads .env first, then .env.local overrides it — $_ENV always reflects .env.local priority.
        // Read env vars directly so .env.local values are used without any container parameter indirection.
        $githubToken = trim((string) ($_ENV['GITHUB_TOKEN'] ?? getenv('GITHUB_TOKEN') ?: ''));
        $legacyToken = trim((string) ($_ENV['ANTHROPIC_API_KEY'] ?? getenv('ANTHROPIC_API_KEY') ?: ''));
        $apiKey = $githubToken !== '' ? $githubToken : $legacyToken;

        $model = trim((string) ($_ENV['GITHUB_RECOMMENDATION_MODEL'] ?? getenv('GITHUB_RECOMMENDATION_MODEL') ?: ''));
        if ($model === '') {
            $model = 'gpt-4o';
        }

        $events = $eventRepository->findAll();
        $chartEvents = $this->formatEventsForCharts($events);
        $stats = $this->calculateStats($events);

        $charityData = $chartEvents['charityData'];
        $topCharity = null;
        if (!empty($charityData)) {
            reset($charityData);
            $topCharityName = key($charityData);
            $topCharityPortions = current($charityData);
            $topCharity = sprintf('%s with %d portions', $topCharityName, $topCharityPortions);
        }

        $statusCounts = $chartEvents['statusCount'];
        $monthlyList = [];
        foreach ($chartEvents['monthly'] as $month => $total) {
            $monthlyList[] = sprintf('%s: %d', $month, $total);
        }

        $prompt = "You are a food donation analyst. Analyze this donation data and provide a short report with: 1) Performance summary 2) Key highlights 3) Actionable recommendations.\n\n" .
            "Data:\n" .
            sprintf("- Total Events: %d\n", $stats['totalEvents']) .
            sprintf("- Total Portions Donated: %d\n", $stats['totalPortions']) .
            sprintf("- Number of Charities: %d\n", $stats['charitiesHelpedCount']) .
            sprintf("- Top Charity: %s\n", $topCharity ?? 'N/A') .
            sprintf("- Cancelled Events: %d\n", $statusCounts[FoodDonationEvent::STATUS_CANCELLED] ?? 0) .
            sprintf("- Scheduled Events: %d\n", $statusCounts[FoodDonationEvent::STATUS_SCHEDULED] ?? 0) .
            sprintf("- Events per month: [%s]\n", implode(', ', $monthlyList)) .
            "\nGive a concise, professional report in 3 short paragraphs.";

        $fallbackReport = $this->buildLocalDonationReport($stats, $chartEvents, $topCharity, $monthlyList);

        $isPlaceholder = $apiKey === ''
            || str_starts_with($apiKey, 'your_')
            || str_contains($apiKey, '_here')
            || strcasecmp($apiKey, 'changeme') === 0;

        if ($isPlaceholder) {
            return new JsonResponse([
                'report' => $fallbackReport,
                'usedFallback' => true,
                'fallbackReason' => 'AI API key not configured, generated local report.',
            ]);
        }

        try {
            $response = $httpClient->request('POST', 'https://models.inference.ai.azure.com/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a food donation analyst. Provide concise professional reports.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => 500,
                ],
            ]);

            $result = $response->toArray(false);
                $aiText = $this->extractAiText($result);

                if ($aiText === '') {
                    $providerError = '';
                    if (isset($result['error']['message']) && is_string($result['error']['message'])) {
                        $providerError = trim($result['error']['message']);
                    }

                    return new JsonResponse([
                        'report' => $fallbackReport,
                        'usedFallback' => true,
                        'fallbackReason' => $providerError !== ''
                            ? ('AI did not return report text: ' . $providerError)
                            : 'AI did not return report text. Generated local report.',
                    ]);
                }

                return new JsonResponse([
                    'report' => $aiText,
                    'usedFallback' => false,
                    'fallbackReason' => '',
                ]);

        } catch (\Exception $e) {
                return new JsonResponse([
                    'report' => $fallbackReport,
                    'usedFallback' => true,
                    'fallbackReason' => 'AI request failed: ' . $e->getMessage(),
                ]);
        }
    }

        /**
         * @param array<string, mixed> $stats
         * @param array<string, mixed> $chartEvents
         * @param array<int, string> $monthlyList
         */
        private function buildLocalDonationReport(array $stats, array $chartEvents, ?string $topCharity, array $monthlyList): string
        {
            $totalEvents = (int) ($stats['totalEvents'] ?? 0);
            $totalPortions = (int) ($stats['totalPortions'] ?? 0);
            $charitiesCount = (int) ($stats['charitiesHelpedCount'] ?? 0);
            $avgPortions = (float) ($stats['avgPortionsPerEvent'] ?? 0);

            $statusCounts = is_array($chartEvents['statusCount'] ?? null) ? $chartEvents['statusCount'] : [];
            $scheduled = (int) ($statusCounts[FoodDonationEvent::STATUS_SCHEDULED] ?? 0);
            $ongoing = (int) ($statusCounts[FoodDonationEvent::STATUS_ONGOING] ?? 0);
            $completed = (int) ($statusCounts[FoodDonationEvent::STATUS_COMPLETED] ?? 0);
            $cancelled = (int) ($statusCounts[FoodDonationEvent::STATUS_CANCELLED] ?? 0);

            $completionRate = $totalEvents > 0 ? round(($completed / $totalEvents) * 100, 1) : 0.0;
            $cancellationRate = $totalEvents > 0 ? round(($cancelled / $totalEvents) * 100, 1) : 0.0;

            $trendSummary = 'No monthly trend available yet.';
            if (count($monthlyList) > 0) {
                $trendSummary = implode(', ', array_slice($monthlyList, -4));
            }

            $paragraph1 = sprintf(
                'Performance summary: %d donation events delivered %d total portions to %d charities, with an average of %.1f portions per event. The current top charity is %s.',
                $totalEvents,
                $totalPortions,
                $charitiesCount,
                $avgPortions,
                $topCharity ?? 'not yet established'
            );

            $paragraph2 = sprintf(
                'Key highlights: Completed events are %d (%.1f%%), ongoing are %d, scheduled are %d, and cancelled are %d (%.1f%%). Recent monthly totals show: %s.',
                $completed,
                $completionRate,
                $ongoing,
                $scheduled,
                $cancelled,
                $cancellationRate,
                $trendSummary
            );

            $paragraph3 = 'Actionable recommendations: prioritize converting scheduled events into completed deliveries, review cancellation causes for route or capacity constraints, and replicate operating patterns from top-performing charities and months to improve consistency.';

            return $paragraph1."\n\n".$paragraph2."\n\n".$paragraph3;
        }

    private function formatEventsForCharts(array $events): array
    {
        $monthlyData = [];
        $statusCount = [
            FoodDonationEvent::STATUS_PENDING => 0,
            FoodDonationEvent::STATUS_SCHEDULED => 0,
            FoodDonationEvent::STATUS_IN_PROGRESS => 0,
            FoodDonationEvent::STATUS_ONGOING => 0,
            FoodDonationEvent::STATUS_COMPLETED => 0,
            FoodDonationEvent::STATUS_CANCELLED => 0,
        ];
        $charityData = [];
        $allEventDates = [];

        foreach ($events as $event) {
            $month = $event->getEventDate()?->format('Y-m') ?? 'Unknown';
            $charity = $event->getCharityName() ?? 'Unknown';
            $status = $this->normalizeEventStatus((string) ($event->getStatus() ?? FoodDonationEvent::STATUS_SCHEDULED));
            $quantity = (int) ($event->getTotalQuantity() ?? 0);

            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = 0;
            }
            $monthlyData[$month] += $quantity;

            if (isset($statusCount[$status])) {
                $statusCount[$status]++;
            }

            if (!isset($charityData[$charity])) {
                $charityData[$charity] = 0;
            }
            $charityData[$charity] += $quantity;

            $allEventDates[] = [
                'date' => $event->getEventDate()?->format('Y-m-d') ?? 'Unknown',
                'quantity' => $quantity,
            ];
        }

        ksort($monthlyData);
        arsort($charityData);

        usort($allEventDates, static function (array $a, array $b): int {
            return strcmp((string) $a['date'], (string) $b['date']);
        });

        return [
            'monthly' => $monthlyData,
            'statusCount' => $statusCount,
            'charityData' => array_slice($charityData, 0, 5, true),
            'allEventDates' => [
                'labels' => array_map(static fn (array $row): string => (string) $row['date'], $allEventDates),
                'quantities' => array_map(static fn (array $row): int => (int) $row['quantity'], $allEventDates),
            ],
            'allEvents' => array_map(static function ($event) {
                $date = $event->getEventDate()?->format('m/d') ?? '—';
                $label = $date . ' - ' . ($event->getCharityName() ?? 'Unknown');
                return [
                    'label' => $label,
                    'quantity' => (int) ($event->getTotalQuantity() ?? 0),
                ];
            }, $events),
        ];
    }

    private function calculateStats(array $events): array
    {
        $totalEvents = count($events);
        $totalPortions = 0;
        $charities = [];

        foreach ($events as $event) {
            $totalPortions += (int) ($event->getTotalQuantity() ?? 0);
            $charity = $event->getCharityName() ?? 'Unknown';
            $charities[$charity] = true;
        }

        $avgPortions = $totalEvents > 0 ? round($totalPortions / $totalEvents, 1) : 0;

        return [
            'totalEvents' => $totalEvents,
            'totalPortions' => $totalPortions,
            'charitiesHelpedCount' => count($charities),
            'avgPortionsPerEvent' => $avgPortions,
        ];
    }

    private function generateCharityColorMap(array $events): array
    {
        $charities = [];
        foreach ($events as $event) {
            $charity = $event->getCharityName() ?? 'Unknown';
            $charities[$charity] = true;
        }

        $uniqueCharities = array_keys($charities);
        sort($uniqueCharities);

        $charityColors = [
            '#3B82F6',
            '#10B981',
            '#F59E0B',
            '#EF4444',
            '#8B5CF6',
            '#EC4899',
        ];

        $colorMap = [];
        foreach ($uniqueCharities as $index => $charity) {
            $colorMap[$charity] = $charityColors[$index % count($charityColors)];
        }

        return $colorMap;
    }

        private function extractAiText(array $result): string
        {
            if (isset($result['choices'][0]['message']['content'])) {
                $content = $result['choices'][0]['message']['content'];
                if (is_string($content)) {
                    return trim($content);
                }

                if (is_array($content)) {
                    $parts = [];
                    foreach ($content as $chunk) {
                        if (is_array($chunk) && isset($chunk['text']) && is_string($chunk['text'])) {
                            $parts[] = $chunk['text'];
                        }
                    }
                    return trim(implode("\n", $parts));
                }
            }

            if (isset($result['choices'][0]['text']) && is_string($result['choices'][0]['text'])) {
                return trim($result['choices'][0]['text']);
            }

            if (isset($result['output_text']) && is_string($result['output_text'])) {
                return trim($result['output_text']);
            }

            return '';
        }

    private function normalizeEventStatus(string $status): string
    {
        return match (strtolower(trim($status))) {
            'scheduled' => FoodDonationEvent::STATUS_SCHEDULED,
            'in_progress', 'in progress' => FoodDonationEvent::STATUS_IN_PROGRESS,
            'ongoing' => FoodDonationEvent::STATUS_ONGOING,
            'completed' => FoodDonationEvent::STATUS_COMPLETED,
            'cancelled' => FoodDonationEvent::STATUS_CANCELLED,
            'pending' => FoodDonationEvent::STATUS_PENDING,
            default => FoodDonationEvent::STATUS_SCHEDULED,
        };
    }

    private function denyUnlessAdmin(Request $request): ?Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return null;
    }
}
