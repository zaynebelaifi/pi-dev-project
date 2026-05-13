<?php
namespace App\MessageHandler;

use App\Message\WhatsAppNotificationMessage;
use App\Service\WhatsAppApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class WhatsAppNotificationHandler implements MessageHandlerInterface
{
    public function __construct(
        private WhatsAppApiService $whatsApp,
        private LoggerInterface $logger
    ) {}

    public function __invoke(WhatsAppNotificationMessage $message): void
    {
        if (trim($message->phone) === '') {
            $this->logger->warning('WhatsApp notification skipped: no phone number for delivery {id}', ['id' => $message->deliveryId]);
            return;
        }
        try {
            $ok = $this->whatsApp->sendMessage($message->phone, $message->text, $message->template, $message->templateParams);
            if (!$ok) {
                $this->logger->warning('Failed to send WhatsApp notification', ['delivery' => $message->deliveryId]);
                throw new \RuntimeException('WhatsApp send failed');
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to send WhatsApp notification', ['delivery' => $message->deliveryId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
