<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create or promote a user to ROLE_ADMIN so they can access the backend'
)]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Admin email address')
            ->addArgument('password', InputArgument::REQUIRED, 'Admin password')
            ->addOption('first-name', null, InputOption::VALUE_OPTIONAL, 'First name', 'Admin')
            ->addOption('last-name', null, InputOption::VALUE_OPTIONAL, 'Last name', 'User')
            ->addOption('phone', null, InputOption::VALUE_OPTIONAL, 'Phone number', null)
            ->addOption('address', null, InputOption::VALUE_OPTIONAL, 'Address', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = strtolower(trim((string) $input->getArgument('email')));
        $password = (string) $input->getArgument('password');
        $firstName = trim((string) $input->getOption('first-name'));
        $lastName = trim((string) $input->getOption('last-name'));
        $phone = trim((string) ($input->getOption('phone') ?? ''));
        $address = trim((string) ($input->getOption('address') ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Please provide a valid email address.');

            return Command::INVALID;
        }

        if ($password === '') {
            $io->error('Please provide a non-empty password.');

            return Command::INVALID;
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        $created = false;

        if (!$user instanceof User) {
            $user = new User();
            $user->setEmail($email);
            $created = true;
            $this->entityManager->persist($user);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRole('ROLE_ADMIN');
        $user->setFirstName($firstName !== '' ? $firstName : 'Admin');
        $user->setLastName($lastName !== '' ? $lastName : 'User');
        $user->setPhone($phone !== '' ? $phone : null);
        $user->setPhoneNumber($phone !== '' ? $phone : null);
        $user->setAddress($address !== '' ? $address : null);
        $user->setIsActive(true);
        $user->setIsVerified(true);

        if (!$user->getCreatedAt()) {
            $user->setCreatedAt(new \DateTimeImmutable());
        }
        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $message = $created
            ? sprintf('Created admin account for %s.', $email)
            : sprintf('Promoted existing user %s to admin.', $email);

        $io->success($message);
        $io->note('Log in with this account, then you will be redirected to the backend because the role is ROLE_ADMIN.');

        return Command::SUCCESS;
    }
}