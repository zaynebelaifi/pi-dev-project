<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TwilioSmsService
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly LoggerInterface $logger,
        private readonly string $accountSid = '',
        private readonly string $authToken = '',
        private readonly string $fromNumber = '',
    ) {
    }

    public function sendMessage(string $phone, string $text): bool
    {
        $phone = trim((string) preg_replace('/[^0-9+]/', '', $phone));
        if ($phone === '') {
            $this->logger->warning('Twilio SMS phone number is missing.');

            return false;
        }

        if (trim($this->accountSid) === '' || trim($this->authToken) === '') {
            $this->logger->warning('Twilio SMS credentials are missing.');

            return false;
        }

        if (trim($this->fromNumber) === '') {
            $this->logger->warning('Twilio SMS sender is missing. Configure TWILIO_FROM_NUMBER.');

            return false;
        }

        try {
            $response = $this->http->request('POST', sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', $this->accountSid), [
                'auth_basic' => [$this->accountSid, $this->authToken],
                'body' => [
                    'To' => $phone,
                    'From' => trim($this->fromNumber),
                    'Body' => $text,
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 200 && $statusCode < 300) {
                return true;
            }

            $this->logger->error('Twilio SMS responded with non-2xx status.', [
                'status' => $statusCode,
                'body' => $response->getContent(false),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Twilio SMS error: ' . $e->getMessage());
        }

        return false;
    }
}
