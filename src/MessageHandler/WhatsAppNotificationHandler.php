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
        $ok = $this->whatsApp->sendMessage($message->phone, $message->text);
        if (!$ok) {
            $this->logger->warning('Failed to send WhatsApp notification', ['delivery' => $message->deliveryId]);
        }
    }
}
