<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
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

    public function getId(): ?int
    {
        return $this->id;
    }

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
    {
        return $this->message;
    }

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

        return $this;
    }

    public function isRead(): bool
    {
        return $this->is_read;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->is_read = $isRead;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
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

        return $this;
    }
}
