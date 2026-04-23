<?php
namespace App\EventListener;

use App\Message\WhatsAppNotificationMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Psr\Log\LoggerInterface;

final class WorkflowListener implements EventSubscriberInterface
{
    public function __construct(
        private MessageBusInterface $bus,
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.transition' => ['onTransition', 0],
        ];
    }

    public function onTransition(TransitionEvent $event): void
    {
        $subject = $event->getSubject();
        if (!is_object($subject)) {
            return;
        }

        // Only act on Delivery entities
        if ($subject::class !== \App\Entity\Delivery::class && !($subject instanceof \App\Entity\Delivery)) {
            return;
        }

        $transition = $event->getTransition()?->getName();
        if (!$transition) {
            return;
        }

        // Send notifications for picked_up and delivered transitions
        if (in_array($transition, ['picked_up', 'delivered'], true)) {
            try {
                $phone = $subject->getRecipient_phone() ?? $subject->getRecipientPhone();
                if ($phone) {
                    $orderId = $subject->getOrder_id() ?? $subject->getOrderId();
                    $recipientName = $subject->getRecipient_name() ?? $subject->getRecipientName() ?? 'Customer';

                    if ($transition === 'delivered') {
                        $template = 'delivery_confirmation_5';
                        $params = [trim((string) $recipientName), (string) $orderId];
                        $text = sprintf('Hi %s, your order #%s was delivered. The delivery man is waiting. Enjoy your meal!', $recipientName, $orderId);
                        $this->bus->dispatch(new WhatsAppNotificationMessage((int) ($subject->getDelivery_id() ?? $subject->getDeliveryId()), (string) $phone, $text, $template, $params));
                    } else {
                        $text = sprintf('Your order #%s status updated: %s', $orderId, strtoupper($transition));
                        $this->bus->dispatch(new WhatsAppNotificationMessage((int) ($subject->getDelivery_id() ?? $subject->getDeliveryId()), (string) $phone, $text));
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error('WorkflowListener error: '.$e->getMessage());
            }
        }
    }
}
