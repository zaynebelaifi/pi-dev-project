<?php

namespace App\Service;

use App\Entity\FoodDonationEvent;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class EventNotificationService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function notifyEventCreated(User $user, FoodDonationEvent $event): void
    {
        $message = sprintf(
            'Food donation event created for %s (%s items).',
            $event->getCharityName() ?? 'charity',
            $event->getTotalQuantity() ?? 0
        );

        $this->createNotification($user, $event, $message);
    }

    public function notifyEventStatusChanged(User $user, FoodDonationEvent $event, ?string $status): void
    {
        $message = sprintf(
            'Donation event #%d status updated to %s.',
            $event->getDonationEventId() ?? 0,
            $status ?: 'UNKNOWN'
        );

        $this->createNotification($user, $event, $message);
    }

    private function createNotification(User $user, FoodDonationEvent $event, string $message): void
    {
        $notification = new Notification();
        $notification->setRecipient($user);
        $notification->setType('INFO');
        $notification->setTitle('Food donation event update');
        $notification->setMessage($message);
        $notification->setRelatedEntity('food_donation_event');
        $notification->setRelatedEntityId($event->getDonationEventId());
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setIsRead(false);

        $this->entityManager->persist($notification);
    }
}
