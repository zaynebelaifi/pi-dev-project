<?php

namespace App\Command;

use App\Repository\EventRegistrationRepository;
use App\Service\TwilioSmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-event-reminders',
    description: 'Send SMS reminders for food donation events starting within one hour.',
)]
class SendEventRemindersCommand extends Command
{
    public function __construct(
        private readonly EventRegistrationRepository $eventRegistrationRepository,
        private readonly TwilioSmsService $twilioSmsService,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable('now');
        $oneHourLater = $now->modify('+1 hour');

        $registrations = $this->eventRegistrationRepository
            ->findRegistrationsNeedingReminder($now, $oneHourLater);

        if ($registrations === []) {
            $io->success('No upcoming events require SMS reminders.');
            return Command::SUCCESS;
        }

        $totalSent = 0;
        $totalFailed = 0;
        $totalSkipped = 0;

        foreach ($registrations as $registration) {
            $event = $registration->getEvent();
            $user = $registration->getUser();
            if ($event === null || $user === null || $event->getDonationEventId() === null) {
                $totalSkipped++;
                continue;
            }

            $eventId = (int) $event->getDonationEventId();
            $eventName = (string) ($event->getCharityName() ?? ('Event #' . $eventId));
            $eventDate = $event->getEventDate()?->format('Y-m-d H:i') ?? 'Date TBD';
            $message = sprintf("Reminder: '%s' starts in 1 hour! (%s)", $eventName, $eventDate);
            $phone = trim((string) ($user->getPhoneNumber() ?: $user->getPhone()));

            if ($phone === '') {
                $totalSkipped++;
                continue;
            }

            $isSent = false;
            try {
                $isSent = $this->twilioSmsService->sendMessage($phone, $message);
            } catch (\Throwable) {
                $isSent = false;
            }

            if ($isSent) {
                $registration->setReminderSentAt($now);
                if ($event->getReminderSentAt() === null) {
                    $event->setReminderSentAt($now);
                }
                $event->setSmsReminderSent(true);
                $totalSent++;
            } else {
                $totalFailed++;
            }

            $io->writeln(sprintf(
                'Event #%d (%s) user #%d: sent=%d failed=%d skipped=%d',
                $eventId,
                (string) ($event->getCharityName() ?? 'Unknown Event'),
                (int) ($user->getId() ?? 0),
                $isSent ? 1 : 0,
                $isSent ? 0 : 1,
                0
            ));
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Reminder job completed: registrations=%d, sent=%d, failed=%d, skipped=%d',
            count($registrations),
            $totalSent,
            $totalFailed,
            $totalSkipped
        ));

        return Command::SUCCESS;
    }
}
