<?php

namespace App\Service;

use App\Entity\FoodDonationEvent;
use App\Entity\User;
use App\Repository\EventRegistrationRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TwilioSmsService
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly LoggerInterface $logger,
        private readonly ?EventRegistrationRepository $eventRegistrationRepository = null,
        private readonly string $accountSid = '',
        private readonly string $authToken = '',
        private readonly string $fromNumber = '',
    ) {
    }

    public function sendMessage(string $phone, string $text): bool
    {
        $phone = trim((string) preg_replace('/[^0-9+]/', '', $phone));
        if ($phone === '') {
            $this->logger->warning('Twilio SMS phone number is missing.');

            return false;
        }

        if (trim($this->accountSid) === '' || trim($this->authToken) === '') {
            $this->logger->warning('Twilio SMS credentials are missing.');

            return false;
        }

        if (trim($this->fromNumber) === '') {
            $this->logger->warning('Twilio SMS sender is missing. Configure TWILIO_FROM_NUMBER.');

            return false;
        }

        try {
            $response = $this->http->request('POST', sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', $this->accountSid), [
                'auth_basic' => [$this->accountSid, $this->authToken],
                'body' => [
                    'To' => $phone,
                    'From' => trim($this->fromNumber),
                    'Body' => $text,
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 200 && $statusCode < 300) {
                return true;
            }

            $this->logger->error('Twilio SMS responded with non-2xx status.', [
                'status' => $statusCode,
                'body' => $response->getContent(false),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Twilio SMS error: ' . $e->getMessage());
        }

        return false;
    }

    public function sendRegistrationConfirmationSms(User $user, FoodDonationEvent $event): bool
    {
        $phone = $this->extractUserPhone($user);
        if ($phone === '') {
            $this->logger->info('Skipping Twilio registration SMS because user has no phone number.', [
                'userId' => $user->getId(),
                'eventId' => $event->getDonationEventId(),
            ]);

            return false;
        }

        $charityName = trim((string) ($event->getCharityName() ?? ('Event #' . (int) $event->getDonationEventId())));
        $eventDate = $event->getEventDate()?->format('M j, Y H:i') ?? 'Date TBD';
        $text = sprintf(
            "You registered for '%s' on %s",
            $charityName,
            $eventDate
        );

        return $this->sendMessage($phone, $text);
    }

    /**
     * @param iterable<User> $customers
     */
    public function sendToMultipleCustomers(iterable $customers, string $message): int
    {
        $successfulSends = 0;

        foreach ($customers as $customer) {
            if (!$customer instanceof User) {
                $this->logger->warning('Skipping bulk Twilio SMS recipient because value is not a User entity.');
                continue;
            }

            $phone = $this->extractUserPhone($customer);
            if ($phone === '') {
                $this->logger->info('Skipping bulk Twilio SMS recipient because customer has no phone number.', [
                    'userId' => $customer->getId(),
                ]);
                continue;
            }

            try {
                if ($this->sendMessage($phone, $message)) {
                    $successfulSends++;
                } else {
                    $this->logger->error('Bulk Twilio SMS failed for customer.', [
                        'userId' => $customer->getId(),
                        'phone' => $phone,
                    ]);
                }
            } catch (\Throwable $exception) {
                $this->logger->error('Bulk Twilio SMS exception for customer.', [
                    'userId' => $customer->getId(),
                    'phone' => $phone,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $successfulSends;
    }

    /**
     * @return array{sent:int,failed:int,skipped:int}
     */
    public function sendEventReminderSms(FoodDonationEvent $event): array
    {
        if ($this->eventRegistrationRepository === null || $event->getDonationEventId() === null) {
            $this->logger->warning('Skipping event reminder SMS because dependencies are missing.', [
                'eventId' => $event->getDonationEventId(),
            ]);

            return ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        }

        $users = $this->eventRegistrationRepository->findRegisteredCustomerUsersForEventId((int) $event->getDonationEventId());
        $eventName = (string) ($event->getCharityName() ?? ('Event #' . (int) $event->getDonationEventId()));
        $message = sprintf("Reminder: '%s' starts in 1 hour!", $eventName);

        $sent = $this->sendToMultipleCustomers($users, $message);
        $failed = max(0, count($users) - $sent);
        $skipped = 0;

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => $skipped];
    }

    private function extractUserPhone(User $user): string
    {
        return trim((string) ($user->getPhoneNumber() ?: $user->getPhone()));
    }
}
