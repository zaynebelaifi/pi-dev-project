<?php

namespace App\Controller;

use App\Entity\FoodDonationEvent;
use App\Repository\EventRegistrationRepository;
use App\Repository\FoodDonationEventRepository;
use App\Repository\FoodDonationItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class EventStatusApiController extends AbstractController
{
    public function __construct(
        private readonly FoodDonationEventRepository $foodDonationEventRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventRegistrationRepository $eventRegistrationRepository,
        private readonly FoodDonationItemRepository $foodDonationItemRepository,
    ) {
    }

    /**
     * GET /api/events/refresh-all
     * Returns all events with live-recalculated statuses, registered user counts, and item counts.
     * Used by admin index, admin show, client show, and calendar pages for 60s AJAX polling.
     */
    #[Route('/api/events/refresh-all', name: 'api_events_refresh_all', methods: ['GET'])]
    public function refreshAll(Request $request): JsonResponse
    {
        $rawIds = $request->query->get('ids', '');
        $eventIds = array_values(array_unique(array_map(
            'intval',
            preg_split('/[^0-9]+/', (string) $rawIds, -1, PREG_SPLIT_NO_EMPTY) ?: []
        )));

        $events = $eventIds !== []
            ? $this->foodDonationEventRepository->createQueryBuilder('e')
                ->andWhere('e.donation_event_id IN (:ids)')
                ->setParameter('ids', $eventIds)
                ->getQuery()->getResult()
            : $this->foodDonationEventRepository->findAll();

        $now = new \DateTimeImmutable('now');
        $hasChanges = false;

        $allIds = array_values(array_filter(array_map(
            static fn (FoodDonationEvent $e): ?int => $e->getDonationEventId(),
            array_filter($events, static fn ($e): bool => $e instanceof FoodDonationEvent)
        )));

        $registrationCounts = $allIds !== [] ? $this->eventRegistrationRepository->countByEventIds($allIds) : [];
        $itemCounts = $allIds !== [] ? $this->foodDonationItemRepository->countByEventIds($allIds) : [];

        $payload = [];

        foreach ($events as $event) {
            if (!$event instanceof FoodDonationEvent) {
                continue;
            }

            $eventId = (int) $event->getDonationEventId();
            $eventDate = $event->getEventDate();

            if ($eventDate instanceof \DateTimeInterface) {
                $newStatus = $this->calculateLiveStatus($eventDate, $now);
                $currentStatus = (string) ($event->getStatus() ?? FoodDonationEvent::STATUS_SCHEDULED);

                if ($currentStatus !== $newStatus && $currentStatus !== FoodDonationEvent::STATUS_CANCELLED) {
                    $event->setStatus($newStatus);
                    $event->setUpdated_at($now);
                    $hasChanges = true;
                }
            }

            $status = (string) ($event->getStatus() ?? FoodDonationEvent::STATUS_SCHEDULED);
            $payload[] = [
                'id'                  => $eventId,
                'status'              => $this->toApiStatus($status),
                'label'               => $status,
                'registeredCount'     => $registrationCounts[$eventId] ?? 0,
                'itemsCount'          => $itemCounts[$eventId] ?? 0,
                'totalQuantity'       => (int) ($event->getTotalQuantity() ?? 0),
                'eventDate'           => $eventDate instanceof \DateTimeInterface ? $eventDate->format('Y-m-d H:i') : null,
                'charityName'         => $event->getCharityName(),
            ];
        }

        if ($hasChanges) {
            $this->entityManager->flush();
        }

        return $this->json([
            'success'   => true,
            'events'    => $payload,
            'updatedAt' => $now->format(DATE_ATOM),
        ]);
    }

    #[Route('/api/refresh-event-statuses', name: 'api_refresh_event_statuses', methods: ['GET'])]
    public function refreshEventStatuses(): JsonResponse
    {
        $events = $this->foodDonationEventRepository->findAll();
        $now = new \DateTimeImmutable('now');
        $updated = [];
        $hasChanges = false;

        foreach ($events as $event) {
            if (!$event instanceof FoodDonationEvent) {
                continue;
            }

            $eventDate = $event->getEventDate();
            if (!$eventDate instanceof \DateTimeInterface) {
                continue;
            }

            $newStatus = $this->calculateLiveStatus($eventDate, $now);
            $currentStatus = (string) ($event->getStatus() ?? FoodDonationEvent::STATUS_SCHEDULED);

            if ($currentStatus !== $newStatus) {
                $event->setStatus($newStatus);
                $event->setUpdated_at($now);
                $hasChanges = true;
            }

            $updated[] = [
                'id' => $event->getDonationEventId(),
                'status' => $this->toApiStatus($newStatus),
                'label' => $newStatus,
            ];
        }

        if ($hasChanges) {
            $this->entityManager->flush();
        }

        return $this->json([
            'success' => true,
            'events' => $updated,
            'updatedAt' => $now->format(DATE_ATOM),
        ]);
    }

    private function calculateLiveStatus(\DateTimeInterface $eventDate, \DateTimeInterface $now): string
    {
        $eventAt = \DateTimeImmutable::createFromInterface($eventDate);
        $current = \DateTimeImmutable::createFromInterface($now);

        $todayStart = $current->setTime(0, 0, 0);
        $tomorrowStart = $todayStart->modify('+1 day');

        if ($eventAt >= $tomorrowStart) {
            return FoodDonationEvent::STATUS_SCHEDULED;
        }

        if ($eventAt < $todayStart) {
            return FoodDonationEvent::STATUS_COMPLETED;
        }

        if ($current < $eventAt) {
            return FoodDonationEvent::STATUS_IN_PROGRESS;
        }

        $endWindow = $eventAt->modify('+2 hours');
        if ($current <= $endWindow) {
            return FoodDonationEvent::STATUS_ONGOING;
        }

        return FoodDonationEvent::STATUS_COMPLETED;
    }

    private function toApiStatus(string $status): string
    {
        return match ($status) {
            FoodDonationEvent::STATUS_IN_PROGRESS => 'IN_PROGRESS',
            FoodDonationEvent::STATUS_ONGOING => 'ONGOING',
            FoodDonationEvent::STATUS_COMPLETED => 'COMPLETED',
            FoodDonationEvent::STATUS_CANCELLED => 'CANCELLED',
            default => 'SCHEDULED',
        };
    }
}
