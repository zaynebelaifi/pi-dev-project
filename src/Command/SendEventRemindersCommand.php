<?php

namespace App\Command;

use App\Repository\FoodDonationEventRepository;
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
        private FoodDonationEventRepository $foodDonationEventRepository,
        private TwilioSmsService $twilioSmsService,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable('now');
        $oneHourLater = $now->modify('+1 hour');

        $events = $this->foodDonationEventRepository
            ->findEventsStartingWithinNextHourWithoutReminder($now, $oneHourLater);

        if ($events === []) {
            $io->success('No upcoming events require SMS reminders.');
            return Command::SUCCESS;
        }

        $totalSent = 0;
        $totalFailed = 0;
        $totalSkipped = 0;

        foreach ($events as $event) {
            $result = $this->twilioSmsService->sendEventReminderSms($event);
            $totalSent += (int) ($result['sent'] ?? 0);
            $totalFailed += (int) ($result['failed'] ?? 0);
            $totalSkipped += (int) ($result['skipped'] ?? 0);

            if ((int) ($result['failed'] ?? 0) === 0) {
                $event->setSmsReminderSent(true);
            }

            $io->writeln(sprintf(
                'Event #%d (%s): sent=%d failed=%d skipped=%d',
                (int) ($event->getDonationEventId() ?? 0),
                (string) ($event->getCharityName() ?? 'Unknown Event'),
                (int) ($result['sent'] ?? 0),
                (int) ($result['failed'] ?? 0),
                (int) ($result['skipped'] ?? 0)
            ));
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Reminder job completed: events=%d, sent=%d, failed=%d, skipped=%d',
            count($events),
            $totalSent,
            $totalFailed,
            $totalSkipped
        ));

        return Command::SUCCESS;
    }
}
