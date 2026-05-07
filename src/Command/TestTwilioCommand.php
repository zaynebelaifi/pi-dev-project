<?php

namespace App\Command;

use App\Service\TwilioSmsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:test-twilio', description: 'Send a test SMS through Twilio.')]
final class TestTwilioCommand extends Command
{
    public function __construct(private readonly TwilioSmsService $twilioSmsService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('phone', InputArgument::REQUIRED, 'Destination phone number in international format (e.g. +21650916717).')
            ->addOption('message', 'm', InputOption::VALUE_REQUIRED, 'Message text to send.', 'BIG 4 Twilio test: SMS configuration is working.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $phone = (string) $input->getArgument('phone');
        $message = (string) $input->getOption('message');

        $io->text(sprintf('Sending Twilio test SMS to %s...', $phone));

        $sent = $this->twilioSmsService->sendMessage($phone, $message);

        if (!$sent) {
            $io->error('Twilio test SMS failed. Check TWILIO_ACCOUNT_SID / TWILIO_AUTH_TOKEN / TWILIO_FROM_NUMBER and logs.');

            return Command::FAILURE;
        }

        $io->success('Twilio test SMS sent successfully.');

        return Command::SUCCESS;
    }
}
