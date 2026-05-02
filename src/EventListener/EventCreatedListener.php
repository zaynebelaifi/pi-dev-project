<?php

namespace App\EventListener;

use App\Event\EventCreatedEvent;
use App\Repository\UserRepository;
use App\Service\TwilioSmsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: EventCreatedEvent::class, method: 'onEventCreated')]
final class EventCreatedListener
{
    public function __construct(
        private readonly TwilioSmsService $twilioSmsService,
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onEventCreated(EventCreatedEvent $event): void
    {
        $foodEvent = $event->getEvent();
        $customers = $this->userRepository->findCustomerUsersWithPhoneNumber();
        if ($customers === []) {
            $this->logger->info('Event created SMS skipped because no customer recipients were found.', [
                'eventId' => $foodEvent->getDonationEventId(),
            ]);

            return;
        }

        $eventName = (string) ($foodEvent->getCharityName() ?? ('Event #' . (int) $foodEvent->getDonationEventId()));
        $eventDate = $foodEvent->getEventDate()?->format('Y-m-d H:i') ?? 'Date TBD';
        $message = sprintf("New event created: '%s' on %s", $eventName, $eventDate);

        $sent = $this->twilioSmsService->sendToMultipleCustomers($customers, $message);
        $failed = max(0, count($customers) - $sent);

        $this->logger->info('Event created SMS dispatch completed.', [
            'eventId' => $foodEvent->getDonationEventId(),
            'recipientCount' => count($customers),
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }
}
