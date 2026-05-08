<?php

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'audit_logs')]
    #[ORM\JoinColumn(name: 'actor_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $actor = null;

    #[ORM\Column(type: 'string', length: 30)]
    private string $action = 'UPDATE';

    #[ORM\Column(type: 'string', length: 60)]
    private string $entity_type = '';

    #[ORM\Column(type: 'integer')]
    private int $entity_id = 0;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $changes = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ip_address = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $user_agent = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function setActor(?User $actor): self
    {
        $this->actor = $actor;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = strtoupper(trim($action));

        return $this;
    }

    public function getEntityType(): string
    {
        return $this->entity_type;
    }

    public function setEntityType(string $entityType): self
    {
        $this->entity_type = $entityType;

        return $this;
    }

    public function getEntityId(): int
    {
        return $this->entity_id;
    }

    public function setEntityId(int $entityId): self
    {
        $this->entity_id = $entityId;

        return $this;
    }

    public function getChanges(): ?array
    {
        return $this->changes;
    }

    public function setChanges(?array $changes): self
    {
        $this->changes = $changes;

        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(?\DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ip_address = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->user_agent = $userAgent;

        return $this;
    }
}
