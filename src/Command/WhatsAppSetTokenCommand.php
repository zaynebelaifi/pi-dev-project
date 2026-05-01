<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

#[AsCommand(name: 'app:whatsapp:set-token', description: 'Set WHATSAPP_API_TOKEN in .env.local and validate it')]
final class WhatsAppSetTokenCommand extends Command
{
    public function __construct(private HttpClientInterface $http, private LoggerInterface $logger)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('token', InputArgument::OPTIONAL, 'The WhatsApp API token to set');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $token = $input->getArgument('token');
        if (!$token) {
            $helper = $this->getHelper('question');
            $question = new \Symfony\Component\Console\Question\Question('Enter token: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $token = $helper->ask($input, $output, $question);
        }

        if (!$token) {
            $output->writeln('<error>No token provided.</error>');
            return Command::FAILURE;
        }

        $envFile = dirname(__DIR__, 2) . '/.env.local';
        $content = '';
        if (file_exists($envFile)) {
            $content = file_get_contents($envFile);
        }

        if (preg_match('/^WHATSAPP_API_TOKEN=.*$/m', $content)) {
            $content = preg_replace('/^WHATSAPP_API_TOKEN=.*$/m', 'WHATSAPP_API_TOKEN="' . addcslashes($token, '"') . '"', $content);
        } else {
            if ($content !== '' && substr($content, -1) !== "\n") $content .= "\n";
            $content .= 'WHATSAPP_API_TOKEN="' . addcslashes($token, '"') . '"' . PHP_EOL;
        }

        file_put_contents($envFile, $content);
        $output->writeln('<info>Updated .env.local</info>');

        // Optionally validate using existing validate endpoint (derive from env)
        $apiUrl = (string) ($_ENV['WHATSAPP_API_URL'] ?? '');
        if (!$apiUrl) {
            $output->writeln('<comment>WHATSAPP_API_URL not configured in environment; token saved but cannot validate automatically.</comment>');
            return Command::SUCCESS;
        }

        $statusUrl = $apiUrl;
        if (str_ends_with($apiUrl, '/messages')) {
            $statusUrl = substr($apiUrl, 0, -strlen('/messages'));
        }

        try {
            $resp = $this->http->request('GET', $statusUrl, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'timeout' => 5,
            ]);
            $status = $resp->getStatusCode();
            $body = $resp->getContent(false);
            if ($status >= 200 && $status < 300) {
                $output->writeln('<info>Token validated successfully.</info>');
                $output->writeln($body);
                return Command::SUCCESS;
            }
            $output->writeln('<error>Token validation failed: HTTP ' . $status . '</error>');
            $output->writeln($body);
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->logger->error('Token validation request failed: ' . $e->getMessage());
            $output->writeln('<error>Validation request failed: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
