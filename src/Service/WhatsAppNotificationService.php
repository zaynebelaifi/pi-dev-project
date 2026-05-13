<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WhatsAppNotificationService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $twilioSid,
        private readonly string $twilioAuthToken,
        private readonly string $twilioWhatsappNumber,
        private readonly string $adminWhatsappNumber,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function notifyUserBanned(User $user, string $identity): bool
    {
        if (
            '' === $this->twilioSid
            || '' === $this->twilioAuthToken
            || '' === $this->twilioWhatsappNumber
            || '' === $this->adminWhatsappNumber
        ) {
            $this->logger->warning('WhatsApp ban notification skipped because Twilio configuration is incomplete.');

            return false;
        }

        try {
            $response = $this->httpClient->request(
                'POST',
                sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', rawurlencode($this->twilioSid)),
                [
                    'auth_basic' => [$this->twilioSid, $this->twilioAuthToken],
                    'body' => [
                        'From' => $this->twilioWhatsappNumber,
                        'To' => $this->adminWhatsappNumber,
                        'Body' => sprintf('User #%d %s has been banned successfully.', $user->getId(), $identity),
                    ],
                ]
            );

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return true;
            }

            $this->logger->warning('Twilio WhatsApp ban notification failed.', [
                'status_code' => $response->getStatusCode(),
                'response' => $response->getContent(false),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->warning('Twilio WhatsApp ban notification failed.', [
                'exception' => $exception->getMessage(),
            ]);
        }

            return false;
    }
}
