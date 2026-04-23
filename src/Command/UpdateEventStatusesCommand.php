<?php

namespace App\Command;

use App\Entity\FoodDonationEvent;
use App\Repository\FoodDonationEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-event-statuses',
    description: 'Automatically updates event statuses based on date and time.'
)]
class UpdateEventStatusesCommand extends Command
{
    public function __construct(
        private FoodDonationEventRepository $foodDonationEventRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $timezone = new \DateTimeZone(date_default_timezone_get());
        $now = new \DateTimeImmutable('now', $timezone);
        $today = $now->setTime(0, 0, 0);
        $tomorrow = $today->modify('+1 day');

        $io->writeln('Updating event statuses...');

        $query = $this->foodDonationEventRepository
            ->createQueryBuilder('e')
            ->where('LOWER(e.status) != :cancelled')
            ->setParameter('cancelled', strtolower(FoodDonationEvent::STATUS_CANCELLED))
            ->getQuery();

        $updatedCount = 0;
        $checkedCount = 0;

        foreach ($query->toIterable() as $event) {
            if (!$event instanceof FoodDonationEvent) {
                continue;
            }

            $checkedCount++;
            $eventDate = $event->getEventDate();
            if (!$eventDate instanceof \DateTimeInterface) {
                continue;
            }

            $eventAt = \DateTimeImmutable::createFromInterface($eventDate)->setTimezone($timezone);
            $eventDayStart = $eventAt->setTime(0, 0, 0);

            $targetStatus = match (true) {
                $eventDayStart >= $tomorrow => FoodDonationEvent::STATUS_SCHEDULED,
                $eventDayStart < $today => FoodDonationEvent::STATUS_COMPLETED,
                $now < $eventAt => FoodDonationEvent::STATUS_IN_PROGRESS,
                default => FoodDonationEvent::STATUS_ONGOING,
            };
            $currentStatus = $this->normalizeStatus((string) ($event->getStatus() ?? FoodDonationEvent::STATUS_SCHEDULED));

            if ($currentStatus === $targetStatus) {
                continue;
            }

            $event->setStatus($targetStatus);
            $updatedCount++;

            $io->writeln(sprintf(
                'Event #%d (%s): %s -> %s',
                (int) $event->getDonationEventId(),
                (string) ($event->getCharityName() ?? 'Unknown Event'),
                $currentStatus,
                $targetStatus
            ));

            if ($updatedCount % 100 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();

        if ($updatedCount === 0) {
            $io->success('No updates needed');
        } else {
            $io->success(sprintf('Successfully updated %d events', $updatedCount));
        }

        $io->note(sprintf('Checked %d non-cancelled events in timezone %s.', $checkedCount, $timezone->getName()));

        return Command::SUCCESS;
    }

    private function normalizeStatus(string $status): string
    {
        return match (strtolower(trim($status))) {
            'scheduled', 'pending' => FoodDonationEvent::STATUS_SCHEDULED,
            'in progress', 'in_progress' => FoodDonationEvent::STATUS_IN_PROGRESS,
            'ongoing' => FoodDonationEvent::STATUS_ONGOING,
            'completed' => FoodDonationEvent::STATUS_COMPLETED,
            'cancelled' => FoodDonationEvent::STATUS_CANCELLED,
            default => FoodDonationEvent::STATUS_SCHEDULED,
        };
    }
}
