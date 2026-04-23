<?php

namespace App\Service;

use App\Entity\FoodDonationEvent;
use App\Entity\User;
use App\Repository\EventRegistrationRepository;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;

class TwilioSmsService
{
    private const E164_REGEX = '/^\+[1-9]\d{7,14}$/';

    private ?object $twilioClient = null;
    private string $twilioSid = '';
    private string $twilioAuthToken = '';
    private string $twilioFromPhone = '';

    public function __construct(
        private EventRegistrationRepository $eventRegistrationRepository,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
    ) {
        $this->twilioSid = $this->readEnv('TWILIO_SID');
        $this->twilioAuthToken = $this->readEnv('TWILIO_AUTH_TOKEN');
        $this->twilioFromPhone = $this->readEnv('TWILIO_PHONE');

        $this->logger->info('Twilio credentials loaded', [
            'has_sid' => $this->twilioSid !== '',
            'has_auth_token' => $this->twilioAuthToken !== '',
            'has_from_phone' => $this->twilioFromPhone !== '',
        ]);

        $this->initializeClient();
    }

    public function sendEventCreatedSms(FoodDonationEvent $event): array
    {
        $this->logger->info(sprintf('SMS service called for event %d', (int) ($event->getDonationEventId() ?? 0)), [
            'context' => 'event_created',
            'eventId' => (int) ($event->getDonationEventId() ?? 0),
        ]);

        $eventDate = $event->getEventDate();
        $dateText = $eventDate instanceof \DateTimeInterface ? $eventDate->format('Y-m-d') : 'TBD';
        $timeText = $eventDate instanceof \DateTimeInterface ? $eventDate->format('H:i') : 'TBD';
        $message = sprintf(
            'New event: %s on %s at %s. Register now! 🎉',
            (string) ($event->getCharityName() ?? 'Food Donation Event'),
            $dateText,
            $timeText,
        );

        return $this->sendToAllCustomers($event, $message, 'event_created');
    }

    public function sendEventEditedSms(FoodDonationEvent $event): array
    {
        $message = sprintf(
            "Event '%s' updated. Check the app! 📱",
            (string) ($event->getCharityName() ?? 'Food Donation Event')
        );

        return $this->sendToAllCustomers($event, $message, 'event_updated');
    }

    public function sendEventReminderSms(FoodDonationEvent $event): array
    {
        $message = sprintf(
            "Reminder: '%s' in 1 hour! ⏰",
            (string) ($event->getCharityName() ?? 'Food Donation Event'),
        );

        return $this->sendToRegisteredCustomers($event, $message, 'event_reminder');
    }

    public function sendRegistrationConfirmationSms(User $user, FoodDonationEvent $event): bool
    {
        $eventDate = $event->getEventDate();
        $dateText = $eventDate instanceof \DateTimeInterface ? $eventDate->format('Y-m-d') : 'TBD';
        $timeText = $eventDate instanceof \DateTimeInterface ? $eventDate->format('H:i') : 'TBD';
        $eventName = (string) ($event->getCharityName() ?? 'Food Donation Event');

        $message = sprintf(
            'You registered for %s on %s at %s! 🎉',
            $eventName,
            $dateText,
            $timeText
        );

        $phone = $this->normalizePhone((string) ($user->getPhoneNumber() ?? $user->getPhone() ?? ''));
        if ($phone === null) {
            $this->logger->warning('Skipping registration confirmation SMS: invalid or missing phone.', [
                'context' => 'event_registration_confirmation',
                'eventId' => (int) ($event->getDonationEventId() ?? 0),
                'userId' => (int) ($user->getId() ?? 0),
            ]);

            return false;
        }

        return $this->sendSms(
            $phone,
            $message,
            'event_registration_confirmation',
            (int) ($event->getDonationEventId() ?? 0),
            (int) ($user->getId() ?? 0)
        );
    }

    private function sendToAllCustomers(FoodDonationEvent $event, string $message, string $context): array
    {
        $eventId = (int) ($event->getDonationEventId() ?? 0);
        if ($eventId <= 0) {
            $this->logger->warning('Skipping SMS send because event ID is invalid.', ['context' => $context]);
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        }

        $users = $this->userRepository->findUsersWithPhoneNumber();

        $this->logger->info(sprintf('Found %d customers to send SMS', count($users)), [
            'context' => $context,
            'eventId' => $eventId,
        ]);

        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }

            $rawPhone = (string) ($user->getPhoneNumber() ?? $user->getPhone() ?? '');
            $normalizedPhone = $this->normalizePhone($rawPhone);
            $this->logger->info('Customer SMS candidate', [
                'context' => $context,
                'eventId' => $eventId,
                'userId' => (int) ($user->getId() ?? 0),
                'email' => (string) ($user->getEmail() ?? ''),
                'phone_raw' => $rawPhone,
                'phone_normalized' => $normalizedPhone,
            ]);
        }

        return $this->sendToUsers($users, $message, $context, $eventId);
    }

    private function sendToRegisteredCustomers(FoodDonationEvent $event, string $message, string $context): array
    {
        $eventId = (int) ($event->getDonationEventId() ?? 0);
        if ($eventId <= 0) {
            $this->logger->warning('Skipping SMS send because event ID is invalid.', ['context' => $context]);
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        }

        $users = $this->eventRegistrationRepository->findRegisteredUsersForEventId($eventId);

        return $this->sendToUsers($users, $message, $context, $eventId);
    }

    /**
     * @param iterable<mixed> $users
     */
    private function sendToUsers(iterable $users, string $message, string $context, int $eventId): array
    {
        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $customerCount = 0;
        $uniquePhoneNumbers = [];
        $recipients = [];

        foreach ($users as $user) {
            if (!$user instanceof User) {
                $skipped++;
                continue;
            }

            if (!$this->isCustomerRole($user->getRole())) {
                $skipped++;
                continue;
            }

            $customerCount++;

            $phone = $this->normalizePhone((string) ($user->getPhoneNumber() ?? $user->getPhone() ?? ''));
            if ($phone === null) {
                $skipped++;
                $this->logger->warning('Skipping SMS: invalid or missing phone number.', [
                    'context' => $context,
                    'eventId' => $eventId,
                    'userId' => $user->getId(),
                ]);
                continue;
            }

            $uniquePhoneNumbers[$phone] = true;

            $recipients[] = [
                'userId' => (int) ($user->getId() ?? 0),
                'phone' => $phone,
            ];
        }

        $this->logger->info(sprintf(
            'Sending SMS to %d customers (%d unique phone numbers)',
            $customerCount,
            count($uniquePhoneNumbers)
        ), [
            'context' => $context,
            'eventId' => $eventId,
        ]);

        foreach ($recipients as $recipient) {
            $this->logger->info(sprintf('Sending SMS to %s', (string) $recipient['phone']), [
                'context' => $context,
                'eventId' => $eventId,
                'userId' => (int) $recipient['userId'],
                'to' => (string) $recipient['phone'],
            ]);

            if ($this->sendSms((string) $recipient['phone'], $message, $context, $eventId, (int) $recipient['userId'])) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $this->logger->info('SMS dispatch completed.', [
            'context' => $context,
            'eventId' => $eventId,
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
        ]);

        $this->logger->info(sprintf('SMS sent to %d customers', $sent), [
            'context' => $context,
            'eventId' => $eventId,
        ]);

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => $skipped];
    }

    private function sendSms(string $to, string $message, string $context, int $eventId, int $userId): bool
    {
        if ($this->twilioClient === null) {
            $this->logger->error('Twilio client is not available. SMS skipped.', [
                'context' => $context,
                'eventId' => $eventId,
                'userId' => $userId,
                'to' => $to,
            ]);
            return false;
        }

        try {
            $this->twilioClient->messages->create($to, [
                'from' => $this->twilioFromPhone,
                'body' => $message,
            ]);

            $this->logger->info('SMS sent successfully.', [
                'context' => $context,
                'eventId' => $eventId,
                'userId' => $userId,
                'to' => $to,
            ]);

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to send SMS.', [
                'context' => $context,
                'eventId' => $eventId,
                'userId' => $userId,
                'to' => $to,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function initializeClient(): void
    {
        if ($this->twilioSid === '' || $this->twilioAuthToken === '' || $this->twilioFromPhone === '') {
            $this->logger->warning('Twilio credentials are missing. SMS service is disabled.');
            return;
        }

        if (!class_exists(\Twilio\Rest\Client::class)) {
            $this->logger->error('Twilio SDK class not found. Run composer install to enable SMS.');
            return;
        }

        try {
            $this->twilioClient = new \Twilio\Rest\Client($this->twilioSid, $this->twilioAuthToken);
            $this->logger->info('Twilio client initialized successfully.');
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to initialize Twilio client.', ['error' => $exception->getMessage()]);
            $this->twilioClient = null;
        }
    }

    private function readEnv(string $name): string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);
        return is_string($value) ? trim($value) : '';
    }

    private function normalizePhone(string $phone): ?string
    {
        $phone = preg_replace('/\s+/', '', trim($phone)) ?? '';
        if ($phone === '') {
            return null;
        }

        return preg_match(self::E164_REGEX, $phone) === 1 ? $phone : null;
    }

    private function isCustomerRole(?string $role): bool
    {
        $role = strtoupper(trim((string) $role));
        if ($role === '') {
            return false;
        }

        return !str_contains($role, 'ADMIN');
    }
}
