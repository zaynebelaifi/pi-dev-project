<?php

namespace App\Controller;

use App\Entity\FoodDonationEvent;
use App\Repository\FoodDonationEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/admin/food-donation/stats')]
final class FoodDonationStatsController extends AbstractController
{
    #[Route(name: 'app_food_donation_stats', methods: ['GET'])]
    public function index(FoodDonationEventRepository $eventRepository): Response
    {
        return $this->render('admin/food_donation_stats/index.html.twig', [
            'activeRoute' => 'app_food_donation_stats',
        ]);
    }

    #[Route('/dashboard', name: 'app_food_donation_stats_dashboard', methods: ['GET'])]
    public function dashboard(FoodDonationEventRepository $eventRepository): Response
    {
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
    public function aiReport(HttpClientInterface $httpClient, FoodDonationEventRepository $eventRepository): JsonResponse
    {
        $apiKey = $this->getParameter('anthropic_api_key');
        if (!$apiKey) {
            return new JsonResponse(['error' => 'API key is not configured.'], 500);
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

        try {
            $response = $httpClient->request('POST', 'https://models.inference.ai.azure.com/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4o',
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
            $aiText = $result['choices'][0]['message']['content'] ?? 'No response returned from AI.';

            return new JsonResponse(['report' => $aiText]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'AI request failed: ' . $e->getMessage()], 500);
        }
    }

    private function formatEventsForCharts(array $events): array
    {
        $monthlyData = [];
        $statusCount = [
            FoodDonationEvent::STATUS_SCHEDULED => 0,
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

    private function normalizeEventStatus(string $status): string
    {
        return match (strtolower(trim($status))) {
            'scheduled' => FoodDonationEvent::STATUS_SCHEDULED,
            'ongoing' => FoodDonationEvent::STATUS_ONGOING,
            'completed' => FoodDonationEvent::STATUS_COMPLETED,
            'cancelled' => FoodDonationEvent::STATUS_CANCELLED,
            'pending' => FoodDonationEvent::STATUS_SCHEDULED,
            default => FoodDonationEvent::STATUS_SCHEDULED,
        };
    }
}