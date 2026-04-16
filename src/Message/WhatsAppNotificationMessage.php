<?php
namespace App\Message;

final class WhatsAppNotificationMessage
{
    public function __construct(
        public int $deliveryId,
        public string $phone,
        public string $text
    ) {}
}
