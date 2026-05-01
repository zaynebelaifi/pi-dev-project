<?php

namespace App\EventListener;

use App\Event\EventRegistrationEvent;
use App\Service\TwilioSmsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: EventRegistrationEvent::class, method: 'onRegistration')]
final class RegistrationListener
{
    public function __construct(
        private readonly TwilioSmsService $twilioSmsService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onRegistration(EventRegistrationEvent $event): void
    {
        try {
            $sent = $this->twilioSmsService->sendRegistrationConfirmationSms($event->getUser(), $event->getEvent());
            $event->markSmsSent($sent);

            $this->logger->info('Event registration SMS handled.', [
                'eventId' => $event->getEvent()->getDonationEventId(),
                'userId' => $event->getUser()->getId(),
                'sent' => $sent,
            ]);
        } catch (\Throwable $exception) {
            $event->markSmsSent(false);

            $this->logger->error('Event registration SMS failed.', [
                'eventId' => $event->getEvent()->getDonationEventId(),
                'userId' => $event->getUser()->getId(),
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
