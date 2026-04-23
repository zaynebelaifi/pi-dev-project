<?php

namespace App\Service;

use App\Entity\Order;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

final class TwilioVoiceConfirmationService
{
    public function __construct(
        private readonly string $accountSid,
        private readonly string $authToken,
        private readonly string $fromPhoneNumber,
        private readonly string $sayVoice = 'alice',
        private readonly string $sayLanguage = 'en-US',
    ) {
    }

    public function sendPaymentConfirmationCall(Order $order, string $recipientPhone, ?string $recipientName = null): void
    {
        $to = $this->normalizePhoneNumber($recipientPhone);
        if ($to === null) {
            throw new \RuntimeException('Voice confirmation call could not be sent because the recipient phone number is invalid.');
        }

        if (
            trim($this->accountSid) === ''
            || trim($this->authToken) === ''
            || trim($this->fromPhoneNumber) === ''
        ) {
            throw new \RuntimeException('Twilio voice confirmation is not configured. Add Twilio credentials to .env.local.');
        }

        $from = $this->normalizePhoneNumber($this->fromPhoneNumber);
        if ($from === null) {
            throw new \RuntimeException('Twilio from phone number is invalid. Use E.164 format (for example +1234567890).');
        }

        $message = $this->buildMessage($order, $recipientName);
        $twiml = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?><Response><Say voice="%s" language="%s">%s</Say></Response>',
            htmlspecialchars($this->sayVoice, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->sayLanguage, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($message, ENT_XML1 | ENT_QUOTES, 'UTF-8')
        );

        $client = new Client($this->accountSid, $this->authToken);

        try {
            $client->calls->create($to, $from, ['twiml' => $twiml]);
        } catch (TwilioException $e) {
            throw new \RuntimeException('Twilio could not place the payment confirmation call: '.$e->getMessage(), 0, $e);
        }
    }

    private function buildMessage(Order $order, ?string $recipientName): string
    {
        $name = trim((string) $recipientName);
        $orderId = (int) ($order->getOrderId() ?? 0);
        $amount = number_format((float) ($order->getTotalAmount() ?? 0), 2);

        return sprintf(
            'Hello %s. This is Big Four Coffee Lounge. Your payment for order number %d, amount %s Tunisian dinars, has been confirmed. Thank you.',
            $name !== '' ? $name : 'there',
            $orderId,
            $amount
        );
    }

    private function normalizePhoneNumber(?string $phone): ?string
    {
        $raw = trim((string) $phone);
        if ($raw === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9+]/', '', $raw);
        if (!is_string($normalized) || $normalized === '') {
            return null;
        }

        if ($normalized[0] !== '+') {
            $normalized = '+'.$normalized;
        }

        if (!preg_match('/^\+[1-9]\d{6,14}$/', $normalized)) {
            return null;
        }

        return $normalized;
    }
}
