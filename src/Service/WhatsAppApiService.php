<?php
namespace App\Service;

use App\Entity\Order;
use Psr\Log\LoggerInterface;
<<<<<<< HEAD
=======
use Symfony\Contracts\HttpClient\HttpClientInterface;
>>>>>>> final2
use Twilio\Rest\Client;

final class WhatsAppApiService
{
    private ?Client $twilio = null;

    public function __construct(
        private readonly LoggerInterface $logger,
<<<<<<< HEAD
        private readonly string $accountSid = '',
        private readonly string $authToken = '',
        private readonly string $fromNumber = 'whatsapp:+14155238886',
=======
        private readonly HttpClientInterface $httpClient,
        private readonly string $accountSid = '',
        private readonly string $authToken = '',
        private readonly string $fromNumber = 'whatsapp:+14155238886',
        private readonly string $graphApiUrl = '',
        private readonly string $graphApiToken = '',
>>>>>>> final2
    ) {
    }

    public function sendPaymentConfirmationCall(Order $order, string $phone): bool
    {
        $message = sprintf(
            "✅ Payment confirmed! Your order #%d has been received. Total: %.2f TND. Thank you for choosing us!",
            (int) ($order->getOrderId() ?? 0),
            (float) ($order->getTotalAmount() ?? 0)
        );

        return $this->sendMessage($phone, $message);
    }

<<<<<<< HEAD
    public function sendMessage(string $phone, string $text): bool
    {
        if (trim($this->accountSid) === '' || trim($this->authToken) === '') {
            $this->logger->warning('Twilio WhatsApp credentials are missing. Configure TWILIO_ACCOUNT_SID and TWILIO_AUTH_TOKEN.');
=======
    public function sendMessage(string $phone, string $text, ?string $template = null, array $templateParams = []): bool
    {
        // Prefer Twilio if credentials are available.
        if (trim($this->accountSid) !== '' && trim($this->authToken) !== '') {
            $twilioOk = $this->sendViaTwilio($phone, $text);
            if ($twilioOk) {
                return true;
            }

            // Fall back to Graph API if Twilio failed and Graph config exists.
            if (trim($this->graphApiUrl) !== '' && trim($this->graphApiToken) !== '') {
                $this->logger->warning('Twilio WhatsApp send failed; attempting Graph API fallback.');

                return $this->sendViaGraphApi($phone, $text, $template, $templateParams);
            }
>>>>>>> final2

            return false;
        }

<<<<<<< HEAD
        $from = trim($this->fromNumber);
        if ($from === '') {
            $this->logger->warning('Twilio WhatsApp sender is missing. Configure TWILIO_WHATSAPP_FROM.');

=======
        // Fallback to Meta Graph API when token/url are configured.
        if (trim($this->graphApiUrl) !== '' && trim($this->graphApiToken) !== '') {
            return $this->sendViaGraphApi($phone, $text, $template, $templateParams);
        }

        $this->logger->warning('WhatsApp credentials are missing. Configure Twilio (TWILIO_ACCOUNT_SID/TWILIO_AUTH_TOKEN) or Graph API (WHATSAPP_API_URL/WHATSAPP_API_TOKEN).');

        return false;
    }

    private function sendViaTwilio(string $phone, string $text): bool
    {
        $from = trim($this->fromNumber);
        if ($from === '') {
            $this->logger->warning('Twilio WhatsApp sender is missing. Configure TWILIO_WHATSAPP_FROM.');
>>>>>>> final2
            return false;
        }

        $to = $this->normalizeWhatsAppPhone($phone);
        if ($to === '') {
            $this->logger->warning('Twilio WhatsApp destination phone is missing or invalid.');
<<<<<<< HEAD
=======
            return false;
        }

        if ($to === $this->normalizeWhatsAppFrom($from)) {
            $this->logger->warning('Twilio WhatsApp rejected: destination and sender are identical.', [
                'to' => $to,
            ]);
>>>>>>> final2

            return false;
        }

        try {
            $this->getClient()->messages->create(
                $to,
                [
                    'from' => $this->normalizeWhatsAppFrom($from),
                    'body' => $text,
                ]
            );

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('WhatsApp Twilio error: ' . $e->getMessage(), [
                'to' => $to,
            ]);

            return false;
        }
    }

<<<<<<< HEAD
=======
    private function sendViaGraphApi(string $phone, string $text, ?string $template = null, array $templateParams = []): bool
    {
        $to = $this->normalizeGraphPhone($phone);
        if ($to === '') {
            $this->logger->warning('WhatsApp Graph API destination phone is missing or invalid.');
            return false;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
        ];

        if ($template !== null && trim($template) !== '') {
            $payload['type'] = 'template';
            $payload['template'] = [
                'name' => trim($template),
                'language' => ['code' => 'en_US'],
            ];

            if ($templateParams !== []) {
                $payload['template']['components'] = [[
                    'type' => 'body',
                    'parameters' => array_map(
                        static fn (string $value): array => ['type' => 'text', 'text' => $value],
                        array_values(array_map(static fn ($v): string => (string) $v, $templateParams))
                    ),
                ]];
            }
        } else {
            $payload['type'] = 'text';
            $payload['text'] = ['body' => $text];
        }

        try {
            $response = $this->httpClient->request('POST', $this->graphApiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->graphApiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 8,
            ]);

            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                $this->logger->error('WhatsApp Graph API error response.', [
                    'status' => $status,
                    'to' => $to,
                    'body' => $response->getContent(false),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('WhatsApp Graph API error: ' . $e->getMessage(), [
                'to' => $to,
            ]);

            return false;
        }
    }

>>>>>>> final2
    private function getClient(): Client
    {
        if ($this->twilio === null) {
            $this->twilio = new Client($this->accountSid, $this->authToken);
        }

        return $this->twilio;
    }

    private function normalizeWhatsAppFrom(string $from): string
    {
        return str_starts_with($from, 'whatsapp:') ? $from : 'whatsapp:' . $from;
    }

    private function normalizeWhatsAppPhone(string $phone): string
    {
        $cleaned = trim((string) preg_replace('/[^0-9+]/', '', $phone));
        if ($cleaned === '') {
            return '';
        }

        if (str_starts_with($cleaned, '00')) {
            $cleaned = '+' . substr($cleaned, 2);
        }

        if (!str_starts_with($cleaned, '+')) {
            // Project defaults to Tunisian numbers when users provide local 8-digit format.
            if (strlen($cleaned) === 8) {
                $cleaned = '+216' . $cleaned;
            } else {
                $cleaned = '+' . ltrim($cleaned, '+');
            }
        }

        return 'whatsapp:' . $cleaned;
    }
<<<<<<< HEAD
=======

    private function normalizeGraphPhone(string $phone): string
    {
        $cleaned = trim((string) preg_replace('/[^0-9+]/', '', $phone));
        if ($cleaned === '') {
            return '';
        }

        if (str_starts_with($cleaned, '00')) {
            $cleaned = '+' . substr($cleaned, 2);
        }

        if (!str_starts_with($cleaned, '+')) {
            if (strlen($cleaned) === 8) {
                $cleaned = '+216' . $cleaned;
            } else {
                $cleaned = '+' . ltrim($cleaned, '+');
            }
        }

        // Graph API expects numeric string without '+'
        return ltrim($cleaned, '+');
    }
>>>>>>> final2
}