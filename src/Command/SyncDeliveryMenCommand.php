<?php

namespace App\Command;

use App\Entity\DeliveryMan;
use App\Entity\Embeddable\Email;
use App\Entity\Embeddable\Phone;
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
            $deliveryMan = $this->entityManager->getRepository(DeliveryMan::class)
                ->createQueryBuilder('dm')
                ->andWhere('LOWER(dm.email.address) = :email')
                ->setParameter('email', strtolower((string) $user->getEmail()))
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            
            if (!$deliveryMan) {
                $deliveryMan = new DeliveryMan();
                $deliveryMan->setEmail(new Email($user->getEmail()));
                $deliveryMan->setName($user->getFirstName() . ' ' . $user->getLastName());
                $deliveryMan->setPhone(new Phone($this->normalizePhone($user->getPhone(), (int) ($user->getId() ?? 0))));
                $deliveryMan->setStatus('active');
                $deliveryMan->setCreatedAt(new \DateTime());
                $deliveryMan->setUpdatedAt(new \DateTime());
                
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

    private function normalizePhone(?string $phone, int $fallbackId): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strlen($digits) >= 8) {
            return substr($digits, -8);
        }

        return str_pad((string) max(1, $fallbackId), 8, '0', STR_PAD_LEFT);
    }
}
