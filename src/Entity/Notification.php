<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
<<<<<<< HEAD
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notifications')]
#[ORM\HasLifecycleCallbacks]
=======

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
>>>>>>> 3e30a5f219658876febfe98b0d7cf8dfd724b166
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
<<<<<<< HEAD
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
=======
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(name: 'recipient_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $recipient = null;

    #[ORM\Column(type: 'string', length: 30)]
    private string $type = 'INFO';

    #[ORM\Column(type: 'string', length: 150)]
    private string $title = '';

    #[ORM\Column(type: 'text')]
    private string $message = '';

    #[ORM\Column(type: 'string', length: 60, nullable: true)]
    private ?string $related_entity = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $related_entity_id = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $is_read = false;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $read_at = null;
>>>>>>> 3e30a5f219658876febfe98b0d7cf8dfd724b166

    public function getId(): ?int
    {
        return $this->id;
    }

<<<<<<< HEAD
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
=======
    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = strtoupper(trim($type));

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getMessage(): string
>>>>>>> 3e30a5f219658876febfe98b0d7cf8dfd724b166
    {
        return $this->message;
    }

<<<<<<< HEAD
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
=======
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getRelatedEntity(): ?string
    {
        return $this->related_entity;
    }

    public function setRelatedEntity(?string $relatedEntity): self
    {
        $this->related_entity = $relatedEntity;

        return $this;
    }

    public function getRelatedEntityId(): ?int
    {
        return $this->related_entity_id;
    }

    public function setRelatedEntityId(?int $relatedEntityId): self
    {
        $this->related_entity_id = $relatedEntityId;

>>>>>>> 3e30a5f219658876febfe98b0d7cf8dfd724b166
        return $this;
    }

    public function isRead(): bool
    {
<<<<<<< HEAD
        return $this->isRead;
=======
        return $this->is_read;
>>>>>>> 3e30a5f219658876febfe98b0d7cf8dfd724b166
    }

    public function setIsRead(bool $isRead): self
    {
<<<<<<< HEAD
        $this->isRead = $isRead;
=======
        $this->is_read = $isRead;

>>>>>>> 3e30a5f219658876febfe98b0d7cf8dfd724b166
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
<<<<<<< HEAD
        return $this->createdAt;
=======
        return $this->created_at;
>>>>>>> 3e30a5f219658876febfe98b0d7cf8dfd724b166
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
<<<<<<< HEAD
        $this->createdAt = $createdAt;
=======
        $this->created_at = $createdAt;

        return $this;
    }

    public function getReadAt(): ?\DateTimeInterface
    {
        return $this->read_at;
    }

    public function setReadAt(?\DateTimeInterface $readAt): self
    {
        $this->read_at = $readAt;

>>>>>>> 3e30a5f219658876febfe98b0d7cf8dfd724b166
        return $this;
    }
}
