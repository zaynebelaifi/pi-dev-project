<?php

namespace App\EventListener;

use App\Event\EventUpdatedEvent;
use App\Repository\EventRegistrationRepository;
use App\Service\TwilioSmsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: EventUpdatedEvent::class, method: 'onEventUpdated')]
final class EventUpdatedListener
{
    public function __construct(
        private readonly TwilioSmsService $twilioSmsService,
        private readonly EventRegistrationRepository $eventRegistrationRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onEventUpdated(EventUpdatedEvent $event): void
    {
        $foodEvent = $event->getEvent();
        if ($foodEvent->getDonationEventId() === null) {
            $event->markSmsDispatchResult(false, 0, 0, 'Event id is missing.');
            $this->logger->warning('Event updated SMS skipped because event id is missing.', [
                'eventId' => $foodEvent->getDonationEventId(),
            ]);

            return;
        }

        $customers = $this->eventRegistrationRepository->findRegisteredUsersForEventId((int) $foodEvent->getDonationEventId());
        if ($customers === []) {
            $event->markSmsDispatchResult(true, 0, 0, null);
            $this->logger->info('Event updated SMS skipped because no registered customer recipients were found.', [
                'eventId' => $foodEvent->getDonationEventId(),
            ]);

            return;
        }

        $eventName = (string) ($foodEvent->getCharityName() ?? ('Event #' . (int) $foodEvent->getDonationEventId()));
        $eventDate = $foodEvent->getEventDate()?->format('Y-m-d H:i') ?? 'Date TBD';
        $message = sprintf("Event '%s' has been updated. New date/time: %s", $eventName, $eventDate);

        $result = $this->twilioSmsService->sendToUsers($customers, $message, 'event_updated', (int) $foodEvent->getDonationEventId());
        $sent = $result['sent'] ?? 0;
        $failed = max(0, count($customers) - $sent);
        $event->markSmsDispatchResult($failed === 0, count($customers), $sent, $failed > 0 ? 'One or more SMS messages failed to send.' : null);

        $this->logger->info('Event updated SMS dispatch completed.', [
            'eventId' => $foodEvent->getDonationEventId(),
            'recipientCount' => count($customers),
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }
}
