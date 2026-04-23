<?php

namespace App\Service;

use App\Entity\Order;
use Twilio\Rest\Client;

final class TwilioVoiceCallService
{
    public function __construct(
        private readonly string $accountSid,
        private readonly string $authToken,
        private readonly string $fromNumber,
    ) {
    }

    /**
     * Places a voice call to the given phone number to confirm the payment for the order.
     */
    public function sendPaymentConfirmationCall(Order $order, string $toPhoneNumber): void
    {
        $toPhoneNumber = trim($toPhoneNumber);
        if ($toPhoneNumber === '') {
            throw new \RuntimeException('Cannot send voice confirmation call: recipient phone number is missing.');
        }

        if (trim($this->accountSid) === '' || trim($this->authToken) === '' || trim($this->fromNumber) === '') {
            throw new \RuntimeException('Twilio is not configured. Add TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, and TWILIO_FROM_NUMBER to .env.local.');
        }

        $client = new Client($this->accountSid, $this->authToken);

        $twiml = $this->buildTwiml($order);

        $client->calls->create(
            $toPhoneNumber,
            $this->fromNumber,
            ['twiml' => $twiml]
        );
    }

    private function buildTwiml(Order $order): string
    {
        $orderNumber = (int) $order->getOrderId();
        $amount = number_format((float) ($order->getTotalAmount() ?? 0), 2);
        $message = sprintf(
            'Hello. This is a confirmation call from BIG 4 Coffee Lounge. '
            . 'Your payment for order number %d has been successfully processed. '
            . 'The total amount charged was %s Tunisian Dinar. '
            . 'Thank you for your order. Goodbye.',
            $orderNumber,
            $amount
        );

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?><Response><Say voice="alice">%s</Say></Response>',
            htmlspecialchars($message, ENT_XML1 | ENT_QUOTES, 'UTF-8')
        );
    }
}
