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

    public function sendMessage(string $phone, string $text, ?string $templateName = null, array $templateParams = []): bool
    {
        if (!$this->apiUrl || !$this->apiToken) {
            $this->logger->warning('WhatsApp API credentials missing');
            return false;
        }

        $normalizedPhone = $this->normalizePhoneNumber($phone);
        if (!$normalizedPhone) {
            $this->logger->warning('WhatsApp phone number invalid or empty');
            return false;
        }

        try {
            $payload = ['messaging_product' => 'whatsapp', 'to' => $normalizedPhone];

            if ($templateName) {
                $template = ['name' => $templateName, 'language' => ['code' => 'en_US']];
                if (!empty($templateParams)) {
                    // build components with body parameters
                    $components = [];
                    $bodyParams = [];
                    foreach ($templateParams as $p) {
                        $bodyParams[] = ['type' => 'text', 'text' => (string) $p];
                    }
                    if (!empty($bodyParams)) {
                        $components[] = ['type' => 'body', 'parameters' => $bodyParams];
                    }
                    if (!empty($components)) {
                        $template['components'] = $components;
                    }
                }
                $payload['type'] = 'template';
                $payload['template'] = $template;
            } else {
                $payload['type'] = 'text';
                $payload['text'] = ['body' => $text];
            }

            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 5,
            ];

            $this->logger->info('WhatsApp API request payload', ['url' => $this->apiUrl, 'payload' => $payload]);
            $resp = $this->http->request('POST', $this->apiUrl, $options);

            $status = $resp->getStatusCode();
            if ($status >= 200 && $status < 300) {
                return true;
            }

            $body = $resp->getContent(false);

            // Detect expired access token (Facebook Graph returns 401 with specific message / subcode)
            if ($status === 401) {
                $decoded = null;
                try {
                    $decoded = json_decode($body, true);
                } catch (\Throwable $e) {
                    // ignore
                }

                $msg = 'WhatsApp API returned 401 Unauthorized.';
                if (is_array($decoded) && isset($decoded['error']['message'])) {
                    $msg .= ' ' . $decoded['error']['message'];
                }
                $msg .= ' Update WHATSAPP_API_TOKEN (in .env) with a valid access token and restart workers.';

                $this->logger->error('WhatsApp API responded with 401 Unauthorized - access token issue', ['status' => $status, 'body' => $body]);
                $this->logger->error($msg);

                // Throw to ensure message is not silently acknowledged by Messenger transports
                throw new \RuntimeException('WhatsApp access token invalid or expired. ' . ($decoded['error']['message'] ?? '')); 
            }

            $this->logger->error('WhatsApp API responded with non-2xx', ['status' => $status, 'body' => $body]);
        } catch (\Throwable $e) {
            $this->logger->error('WhatsApp API error: ' . $e->getMessage());
            // Re-throw runtime exceptions so message processing surfaces failures to Messenger
            if ($e instanceof \RuntimeException) {
                throw $e;
            }
        }
        return false;
    }

    private function normalizePhoneNumber(string $phone): ?string
    {
        $trimmed = trim($phone);
        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $trimmed);
        if (!$digits) {
            return null;
        }

        if (str_starts_with($digits, '216')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '216' . ltrim($digits, '0');
        }

        return '216' . $digits;
    }
}
