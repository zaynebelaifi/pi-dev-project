<?php

namespace App\Service;

use App\Entity\FoodDonationEvent;
use App\Entity\User;
use App\Repository\EventRegistrationRepository;
use App\Repository\FoodDonationEventRepository;
use GeminiAPI\Client as GeminiClient;
use GeminiAPI\Resources\Parts\TextPart;

class EventRecommendationService
{
    public function __construct(
        private readonly GeminiClient $geminiClient,
        private readonly FoodDonationEventRepository $eventRepository,
        private readonly EventRegistrationRepository $registrationRepository,
    ) {
    }

    public function getRecommendations(User $user): array
    {
        $preferences = $this->extractUserPreferences($user);

        $existingRegistrations = $this->registrationRepository->findForUserId((int) $user->getId());
        $registeredIds = [];
        foreach ($existingRegistrations as $registration) {
            $event = $registration->getEvent();
            if ($event instanceof FoodDonationEvent && $event->getDonationEventId() !== null) {
                $registeredIds[] = (int) $event->getDonationEventId();
            }
        }

        $candidates = $this->eventRepository->findRecommendationCandidates($registeredIds, 20);
        if ($candidates === []) {
            return [];
        }

        $candidatePayload = array_map(static function (FoodDonationEvent $event): array {
            return [
                'id' => (int) $event->getDonationEventId(),
                'charity' => (string) ($event->getCharityName() ?? 'Unknown'),
                'date' => $event->getEventDate()?->format('Y-m-d H:i'),
                'status' => (string) ($event->getStatus() ?? FoodDonationEvent::STATUS_SCHEDULED),
                'quantity' => (int) ($event->getTotalQuantity() ?? 0),
            ];
        }, $candidates);

        $prompt = $this->buildRecommendationPrompt($preferences, $candidatePayload);

        $aiPicks = [];
        try {
            $aiRawResponse = $this->callGeminiAPI($prompt);
            $aiPicks = $this->parseGeminiResponse($aiRawResponse);
        } catch (\Throwable) {
            $aiPicks = [];
        }

        $candidateMap = [];
        foreach ($candidates as $candidate) {
            if (!$candidate instanceof FoodDonationEvent || $candidate->getDonationEventId() === null) {
                continue;
            }
            $candidateMap[(int) $candidate->getDonationEventId()] = $candidate;
        }

        $selected = [];
        foreach ($aiPicks as $pick) {
            $eventId = (int) ($pick['id'] ?? 0);
            if ($eventId <= 0 || !isset($candidateMap[$eventId])) {
                continue;
            }

            $selected[] = [
                'event' => $candidateMap[$eventId],
                'reason' => (string) ($pick['reason'] ?? 'Recommended based on your previous registrations.'),
                'match_percentage' => null,
            ];

            unset($candidateMap[$eventId]);

            if (count($selected) >= 5) {
                break;
            }
        }

        if (count($selected) < 5) {
            foreach ($candidateMap as $fallbackEvent) {
                $selected[] = [
                    'event' => $fallbackEvent,
                    'reason' => 'Relevant to your event history and upcoming community activity.',
                    'match_percentage' => null,
                ];

                if (count($selected) >= 5) {
                    break;
                }
            }
        }

        $selected = $this->enrichRecommendationsWithMatchScore($selected);

        $eventIds = array_map(
            static fn (array $row): int => (int) $row['event']->getDonationEventId(),
            $selected
        );

        $registrationCounts = $this->registrationRepository->countByEventIds($eventIds);

        $result = [];
        foreach ($selected as $row) {
            /** @var FoodDonationEvent $event */
            $event = $row['event'];
            $eventId = (int) $event->getDonationEventId();
            $eventDate = $event->getEventDate();
            $computedStatus = $this->toApiStatus($this->computeLiveStatus($event));

            $result[] = [
                'id' => $eventId,
                'name' => sprintf('%s Event', (string) ($event->getCharityName() ?? 'Donation')),
                'charity' => (string) ($event->getCharityName() ?? 'Unknown Charity'),
                'date' => $eventDate?->format('Y-m-d'),
                'time' => $eventDate?->format('H:i'),
                'description' => sprintf(
                    'Join BIG 4 community donation with %d planned item(s).',
                    (int) ($event->getTotalQuantity() ?? 0)
                ),
                'status' => $computedStatus,
                'registered' => (int) ($registrationCounts[$eventId] ?? 0),
                'match_percentage' => (int) ($row['match_percentage'] ?? 70),
                'reason' => (string) ($row['reason'] ?? 'Recommended based on your previous registrations.'),
                'event_datetime' => $eventDate?->format(DATE_ATOM),
            ];
        }

        return $result;
    }

    public function extractUserPreferences(User $user): array
    {
        $registrations = $this->registrationRepository->findForUserId((int) $user->getId());

        $charityCounts = [];
        $weekdayCounts = [];
        $hourBuckets = [
            'morning' => 0,
            'afternoon' => 0,
            'evening' => 0,
        ];

        foreach ($registrations as $registration) {
            $event = $registration->getEvent();
            if (!$event instanceof FoodDonationEvent) {
                continue;
            }

            $charity = strtolower(trim((string) ($event->getCharityName() ?? '')));
            if ($charity !== '') {
                $charityCounts[$charity] = ($charityCounts[$charity] ?? 0) + 1;
            }

            $eventDate = $event->getEventDate();
            if (!$eventDate instanceof \DateTimeInterface) {
                continue;
            }

            $weekday = strtolower($eventDate->format('l'));
            $weekdayCounts[$weekday] = ($weekdayCounts[$weekday] ?? 0) + 1;

            $hour = (int) $eventDate->format('H');
            if ($hour < 12) {
                $hourBuckets['morning']++;
            } elseif ($hour < 18) {
                $hourBuckets['afternoon']++;
            } else {
                $hourBuckets['evening']++;
            }
        }

        arsort($charityCounts);
        arsort($weekdayCounts);
        arsort($hourBuckets);

        return [
            'top_charities' => array_slice(array_keys($charityCounts), 0, 5),
            'favorite_days' => array_slice(array_keys($weekdayCounts), 0, 3),
            'preferred_time_bucket' => array_key_first($hourBuckets) ?: 'evening',
            'total_registrations' => count($registrations),
        ];
    }

    public function callGeminiAPI(string $prompt): string
    {
        $response = $this->geminiClient
            ->generativeModel('gemini-1.5-flash')
            ->generateContent(new TextPart($prompt));

        return trim((string) $response->text());
    }

    public function parseGeminiResponse(string $response): array
    {
        $clean = trim($response);

        if (str_starts_with($clean, '```')) {
            $clean = preg_replace('/^```[a-zA-Z]*\s*/', '', $clean) ?? $clean;
            $clean = preg_replace('/```$/', '', $clean) ?? $clean;
            $clean = trim($clean);
        }

        $decoded = json_decode($clean, true);
        if (!is_array($decoded)) {
            return [];
        }

        $items = $decoded['recommendations'] ?? $decoded;
        if (!is_array($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = (int) ($item['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $result[] = [
                'id' => $id,
                'reason' => (string) ($item['reason'] ?? 'Recommended based on your profile.'),
            ];
        }

        return $result;
    }

    public function enrichRecommendationsWithMatchScore(array $events): array
    {
        foreach ($events as $index => $row) {
            /** @var FoodDonationEvent|null $event */
            $event = $row['event'] ?? null;
            $reason = strtolower((string) ($row['reason'] ?? ''));

            $score = 62;

            if ($event instanceof FoodDonationEvent) {
                $eventDate = $event->getEventDate();
                if ($eventDate instanceof \DateTimeInterface) {
                    $daysUntil = (int) floor((($eventDate->getTimestamp() - time()) / 86400));
                    if ($daysUntil >= 0 && $daysUntil <= 10) {
                        $score += 12;
                    }
                }

                $status = strtolower((string) ($event->getStatus() ?? 'scheduled'));
                if (str_contains($status, 'scheduled') || str_contains($status, 'in progress')) {
                    $score += 8;
                }
            }

            if (str_contains($reason, 'similar')) {
                $score += 10;
            }
            if (str_contains($reason, 'charity')) {
                $score += 8;
            }
            if (str_contains($reason, 'preference')) {
                $score += 6;
            }

            $score += random_int(0, 8);
            $events[$index]['match_percentage'] = max(50, min(100, $score));
        }

        usort($events, static fn (array $a, array $b): int => (int) ($b['match_percentage'] ?? 0) <=> (int) ($a['match_percentage'] ?? 0));

        return $events;
    }

    private function buildRecommendationPrompt(array $preferences, array $candidates): string
    {
        return sprintf(
            "You are an event recommendation assistant for BIG 4 Coffee Lounge.\n" .
            "User preferences: %s\n" .
            "Candidate events: %s\n" .
            "Select up to 5 best event IDs for the user.\n" .
            "Return STRICT JSON with this exact shape: {\"recommendations\":[{\"id\":123,\"reason\":\"...\"}]}\n" .
            "Rules:\n" .
            "- Use only IDs from candidate events\n" .
            "- Keep reasons concise (max 18 words)\n" .
            "- No markdown, no code fences, no extra text",
            json_encode($preferences, JSON_UNESCAPED_SLASHES),
            json_encode($candidates, JSON_UNESCAPED_SLASHES)
        );
    }

    private function computeLiveStatus(FoodDonationEvent $event): string
    {
        $eventDate = $event->getEventDate();
        if (!$eventDate instanceof \DateTimeInterface) {
            return (string) ($event->getStatus() ?? FoodDonationEvent::STATUS_SCHEDULED);
        }

        $current = (string) ($event->getStatus() ?? FoodDonationEvent::STATUS_SCHEDULED);
        if (strtolower($current) === strtolower(FoodDonationEvent::STATUS_CANCELLED)) {
            return FoodDonationEvent::STATUS_CANCELLED;
        }

        return FoodDonationEvent::calculateAutoStatus($eventDate, new \DateTimeImmutable('now'));
    }

    private function toApiStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'scheduled', 'pending' => 'SCHEDULED',
            'in progress', 'in_progress' => 'IN_PROGRESS',
            'ongoing' => 'ONGOING',
            'completed' => 'COMPLETED',
            'cancelled' => 'CANCELLED',
            default => 'SCHEDULED',
        };
    }
}
