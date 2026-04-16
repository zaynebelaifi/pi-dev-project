<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

final class WhatsAppApiService
{
    public function __construct(
        private HttpClientInterface $http,
        private LoggerInterface $logger,
        private string $apiUrl = '',
        private ?string $apiToken = null
    ){
        if (!$this->apiUrl) {
            $this->apiUrl = (string) ($_ENV['WHATSAPP_API_URL'] ?? '');
        }
        if (!$this->apiToken) {
            $this->apiToken = $_ENV['WHATSAPP_API_TOKEN'] ?? null;
        }
    }

    public function sendMessage(string $phone, string $text): bool
    {
        if (!$this->apiUrl || !$this->apiToken) {
            $this->logger->warning('WhatsApp API credentials missing');
            return false;
        }

        try {
            $resp = $this->http->request('POST', $this->apiUrl, [
                'headers' => ['Authorization' => 'Bearer ' . $this->apiToken, 'Content-Type' => 'application/json'],
                'json' => [
                    'to' => $phone,
                    'type' => 'text',
                    'text' => ['body' => $text],
                ],
                'timeout' => 5,
            ]);

            if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300) {
                return true;
            }
            $this->logger->error('WhatsApp API responded with non-2xx', ['status' => $resp->getStatusCode(), 'body' => $resp->getContent(false)]);
        } catch (\Throwable $e) {
            $this->logger->error('WhatsApp API error: ' . $e->getMessage());
        }
        return false;
    }
}
