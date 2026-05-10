<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:whatsapp:list-templates', description: 'List WhatsApp message templates visible to the configured phone number')]
final class WhatsAppListTemplatesCommand extends Command
{
    public function __construct(private HttpClientInterface $http)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiUrl = (string) ($_ENV['WHATSAPP_API_URL'] ?? '');
        $token = $_ENV['WHATSAPP_API_TOKEN'] ?? null;
        if (!$apiUrl || !$token) {
            $output->writeln('<error>WHATSAPP_API_URL or WHATSAPP_API_TOKEN is not configured.</error>');
            return Command::FAILURE;
        }

        // try to extract phone_number_id from URL like https://graph.facebook.com/v25.0/1043118648890291/messages
        if (!preg_match('#/v\d+\.\d+/(\d+)(?:/|$)#', $apiUrl, $m)) {
            $output->writeln('<error>Could not extract phone number id from WHATSAPP_API_URL.</error>');
            $output->writeln('Set WHATSAPP_API_URL to https://graph.facebook.com/v<version>/<PHONE_NUMBER_ID>/messages');
            return Command::FAILURE;
        }

        $phoneId = $m[1];
        $url = sprintf('https://graph.facebook.com/v25.0/%s/message_templates', $phoneId);

        try {
            $resp = $this->http->request('GET', $url, ['headers' => ['Authorization' => 'Bearer ' . $token], 'timeout' => 8]);
            $status = $resp->getStatusCode();
            $body = $resp->getContent(false);
            if ($status >= 200 && $status < 300) {
                $output->writeln('<info>Templates response:</info>');
                $decoded = json_decode($body, true);
                $output->writeln(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                return Command::SUCCESS;
            }
            $output->writeln(sprintf('<error>HTTP %d</error>', $status));
            $output->writeln($body);
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $output->writeln('<error>Request failed: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
