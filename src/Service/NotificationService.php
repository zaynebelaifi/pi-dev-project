<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository,
    ) {
    }

    public function createNotification(
        User $recipient,
        string $type,
        string $title,
        string $message,
        ?string $relatedEntity = null,
        ?int $relatedEntityId = null,
    ): Notification {
        $notification = (new Notification())
            ->setRecipient($recipient)
            ->setType($type)
            ->setTitle($title)
            ->setMessage($message)
            ->setRelatedEntity($relatedEntity)
            ->setRelatedEntityId($relatedEntityId)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setIsRead(false);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * @return Notification[]
     */
    public function getNotifications(User $recipient, bool $unreadOnly = false): array
    {
        return $this->notificationRepository->findByRecipient($recipient, $unreadOnly);
    }

    public function markAsRead(Notification $notification): Notification
    {
        $notification->setIsRead(true)->setReadAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $notification;
    }

    public function deleteNotification(Notification $notification): void
    {
        $this->entityManager->remove($notification);
        $this->entityManager->flush();
    }
}
