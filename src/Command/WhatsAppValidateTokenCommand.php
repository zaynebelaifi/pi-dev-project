<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

#[AsCommand(name: 'app:whatsapp:validate-token', description: 'Validate the configured WhatsApp Graph API token')]
final class WhatsAppValidateTokenCommand extends Command
{
    public function __construct(private HttpClientInterface $http, private LoggerInterface $logger)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiUrl = (string) ($_ENV['WHATSAPP_API_URL'] ?? '');
        $token = $_ENV['WHATSAPP_API_TOKEN'] ?? null;

        if (!$apiUrl || !$token) {
            $output->writeln('<error>WHATSAPP_API_URL or WHATSAPP_API_TOKEN not configured in environment.</error>');
            return Command::FAILURE;
        }

        // If apiUrl looks like .../<PHONE_ID>/messages, query the phone id URL to validate token
        $statusUrl = $apiUrl;
        if (str_ends_with($apiUrl, '/messages')) {
            $statusUrl = substr($apiUrl, 0, -strlen('/messages'));
        }

        $output->writeln(sprintf('Checking token against: %s', $statusUrl));

        try {
            $resp = $this->http->request('GET', $statusUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'timeout' => 5,
            ]);

            $status = $resp->getStatusCode();
            $body = $resp->getContent(false);

            if ($status >= 200 && $status < 300) {
                $output->writeln('<info>Token appears valid (HTTP ' . $status . ').</info>');
                $output->writeln($body);
                return Command::SUCCESS;
            }

            $output->writeln('<error>Non-2xx response: ' . $status . '</error>');
            $output->writeln($body);
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->logger->error('Token validation request failed: ' . $e->getMessage());
            $output->writeln('<error>Request failed: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
