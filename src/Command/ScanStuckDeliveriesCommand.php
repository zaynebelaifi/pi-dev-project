<?php

namespace App\Command;

use App\Repository\DeliveryRepository;
use App\Service\AIPriorityService;
use App\Service\LogisticsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:deliveries:scan-stuck',
    description: 'Scan in-transit deliveries and flag those delayed beyond ETA + 20%.'
)]
class ScanStuckDeliveriesCommand extends Command
{
    public function __construct(
        private DeliveryRepository $deliveryRepository,
        private LogisticsService $logisticsService,
        private AIPriorityService $aiPriorityService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $deliveries = $this->deliveryRepository->findBy(['status' => 'IN_TRANSIT']);
        if (empty($deliveries)) {
            $io->info('No in-transit deliveries found.');
            return Command::SUCCESS;
        }

        $flagged = 0;
        foreach ($deliveries as $delivery) {
            $eta = $this->logisticsService->calculateETA($delivery);
            $etaSeconds = $eta['duration'] ?? null;
            if (!$etaSeconds) {
                continue;
            }

            if ($this->aiPriorityService->isStuck($delivery, (int) $etaSeconds)) {
                $notes = (string) ($delivery->getDelivery_notes() ?? $delivery->getDeliveryNotes() ?? '');
                if (stripos($notes, '[FLAGGED_STUCK]') === false) {
                    $notes = trim($notes . '\n[FLAGGED_STUCK] Auto-flagged: exceeded ETA by 20%');
                    $delivery->setDelivery_notes($notes);
                    $flagged++;
                }
            }
        }

        if ($flagged > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Flagged %d delivery(ies) as stuck.', $flagged));
        } else {
            $io->info('No stuck deliveries found.');
        }

        return Command::SUCCESS;
    }
}
