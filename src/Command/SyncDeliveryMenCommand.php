<?php

namespace App\Command;

use App\Entity\DeliveryMan;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-delivery-men',
    description: 'Sync delivery men from user table to delivery_man table'
)]
class SyncDeliveryMenCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $deliveryUsers = $this->userRepository->findBy(['role' => 'ROLE_DELIVERY_MAN']);
        
        if (empty($deliveryUsers)) {
            $io->info('No delivery men found in user table.');
            return Command::SUCCESS;
        }

        $created = 0;
        foreach ($deliveryUsers as $user) {
            // Check if delivery man already exists
            $deliveryMan = $this->entityManager->getRepository(DeliveryMan::class)->findOneBy(['email' => $user->getEmail()]);
            
            if (!$deliveryMan) {
                $deliveryMan = new DeliveryMan();
                $deliveryMan->setEmail($user->getEmail());
                $deliveryMan->setName($user->getFirstName() . ' ' . $user->getLastName());
                $deliveryMan->setPhone($user->getPhone() ?? 'N/A');
                $deliveryMan->setStatus('active');
                $deliveryMan->setCreated_at(new \DateTime());
                $deliveryMan->setUpdated_at(new \DateTime());
                
                $this->entityManager->persist($deliveryMan);
                $created++;
            }
        }

        if ($created > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Created %d delivery man record(s).', $created));
        } else {
            $io->info('All delivery men already exist in delivery_man table.');
        }

        return Command::SUCCESS;
    }
}
