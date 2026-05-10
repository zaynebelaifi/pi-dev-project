<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notifications')]
#[ORM\HasLifecycleCallbacks]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'notification_id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: FoodDonationEvent::class)]
    #[ORM\JoinColumn(name: 'donation_event_id', referencedColumnName: 'donation_event_id', nullable: true, onDelete: 'CASCADE')]
    private ?FoodDonationEvent $event = null;

    #[ORM\Column(type: 'string', length: 500)]
    #[Assert\NotBlank(message: 'Notification message is required.')]
    #[Assert\Length(max: 500, maxMessage: 'Notification message cannot exceed {{ limit }} characters.')]
    private ?string $message = null;

    #[ORM\Column(name: 'notification_type', type: 'string', length: 255)]
    private ?string $notificationType = 'BOTH';

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $status = 'PENDING';

    #[ORM\Column(name: 'scheduled_time', type: 'datetime')]
    #[Assert\NotNull(message: 'Scheduled time is required.')]
    private ?\DateTimeInterface $scheduledTime = null;

    #[ORM\Column(name: 'sent_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\Column(name: 'is_read', type: 'boolean', options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $now = new \DateTimeImmutable();
        if (null === $this->createdAt) {
            $this->createdAt = $now;
        }
        if (null === $this->scheduledTime) {
            $this->scheduledTime = $now;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getEvent(): ?FoodDonationEvent
    {
        return $this->event;
    }

    public function setEvent(?FoodDonationEvent $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getNotificationType(): ?string
    {
        return $this->notificationType;
    }

    public function setNotificationType(?string $notificationType): self
    {
        $this->notificationType = $notificationType;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getScheduledTime(): ?\DateTimeInterface
    {
        return $this->scheduledTime;
    }

    public function setScheduledTime(?\DateTimeInterface $scheduledTime): self
    {
        $this->scheduledTime = $scheduledTime;
        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeInterface $sentAt): self
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
