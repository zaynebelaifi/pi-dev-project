<?php

namespace App\Command;

use App\Repository\EventRegistrationRepository;
use App\Service\TwilioSmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:event-reminder', description: 'Send reminder SMS for events starting soon (run every 5-10 minutes).')]
final class EventReminderCommand extends Command
{
    /**
     * @var string[]
     */
    private const CUSTOMER_ROLES = ['ROLE_CLIENT', 'ROLE_CUSTOMER'];

    public function __construct(
        private readonly EventRegistrationRepository $eventRegistrationRepository,
        private readonly TwilioSmsService $twilioSmsService,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $defaultWindow = (string) ((int) ($_ENV['EVENT_REMINDER_WINDOW_MINUTES'] ?? getenv('EVENT_REMINDER_WINDOW_MINUTES') ?: 60));

        $this
            ->addOption('window-minutes', null, InputOption::VALUE_REQUIRED, 'Reminder window in minutes from now.', $defaultWindow)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only display matching events without sending SMS.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $windowMinutes = max(1, (int) $input->getOption('window-minutes'));
        $isDryRun = (bool) $input->getOption('dry-run');

        $now = new \DateTimeImmutable('now');
        $windowEnd = $now->modify(sprintf('+%d minutes', $windowMinutes));

        $registrations = $this->eventRegistrationRepository->findRegistrationsNeedingReminder($now, $windowEnd);

        if ($registrations === []) {
            $io->success('No upcoming events require reminders in this window.');

            return Command::SUCCESS;
        }

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($registrations as $registration) {
            $event = $registration->getEvent();
            $user = $registration->getUser();
            if ($event === null || $user === null || $event->getDonationEventId() === null) {
                $skipped++;
                continue;
            }

            $eventId = (int) $event->getDonationEventId();

            if ($isDryRun) {
                $io->writeln(sprintf(
                    '[DRY-RUN] Event #%d (%s) user #%d at %s',
                    $eventId,
                    (string) ($event->getCharityName() ?? 'Unknown event'),
                    (int) ($user->getId() ?? 0),
                    $event->getEventDate()?->format('Y-m-d H:i') ?? 'Date TBD'
                ));
                continue;
            }

            $eventName = (string) ($event->getCharityName() ?? ('Event #' . $eventId));
            $message = sprintf("Reminder: '%s' starts in 1 hour!", $eventName);

            $role = strtoupper(trim((string) ($user->getRole() ?? '')));
            if (!in_array($role, self::CUSTOMER_ROLES, true)) {
                $skipped++;
                continue;
            }

            $phone = trim((string) ($user->getPhoneNumber() ?: $user->getPhone()));
            if ($phone === '') {
                $skipped++;
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
                $sent++;
            } else {
                $failed++;
            }

            $io->writeln(sprintf(
                'Event #%d (%s) user #%d: sent=%d failed=%d skipped=%d',
                $eventId,
                (string) ($event->getCharityName() ?? 'Unknown event'),
                (int) ($user->getId() ?? 0),
                $isSent ? 1 : 0,
                $isSent ? 0 : 1,
                0,
            ));
        }

        if (!$isDryRun) {
            $this->entityManager->flush();
            $io->success(sprintf('Reminder run completed. sent=%d failed=%d skipped=%d', $sent, $failed, $skipped));
        } else {
            $io->success(sprintf('Dry-run completed. matching registrations=%d', count($registrations)));
        }

        return Command::SUCCESS;
    }
}
