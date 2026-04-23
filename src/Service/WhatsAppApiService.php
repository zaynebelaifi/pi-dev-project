<?php
namespace App\Service;

use App\Entity\Order;
use Psr\Log\LoggerInterface;
use Twilio\Rest\Client;

final class WhatsAppApiService
{
    private ?Client $twilio = null;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $accountSid = '',
        private readonly string $authToken = '',
        private readonly string $fromNumber = 'whatsapp:+14155238886',
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

    public function sendMessage(string $phone, string $text): bool
    {
        if (trim($this->accountSid) === '' || trim($this->authToken) === '') {
            $this->logger->warning('Twilio WhatsApp credentials are missing. Configure TWILIO_ACCOUNT_SID and TWILIO_AUTH_TOKEN.');

            return false;
        }

        $from = trim($this->fromNumber);
        if ($from === '') {
            $this->logger->warning('Twilio WhatsApp sender is missing. Configure TWILIO_WHATSAPP_FROM.');

            return false;
        }

        $to = $this->normalizeWhatsAppPhone($phone);
        if ($to === '') {
            $this->logger->warning('Twilio WhatsApp destination phone is missing or invalid.');

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
}