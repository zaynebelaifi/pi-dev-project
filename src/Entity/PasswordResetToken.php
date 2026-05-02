<?php

namespace App\Entity;

use App\Repository\PasswordResetTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PasswordResetTokenRepository::class)]
#[ORM\Table(name: 'password_reset_token')]
#[ORM\Index(columns: ['token_hash'], name: 'idx_password_reset_token_hash')]
#[ORM\Index(columns: ['expires_at'], name: 'idx_password_reset_expires_at')]
class PasswordResetToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 64)]
    private ?string $token_hash = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $expires_at = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $used_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTokenHash(): ?string
    {
        return $this->token_hash;
    }

    public function setTokenHash(string $tokenHash): self
    {
        $this->token_hash = $tokenHash;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expires_at;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expires_at = $expiresAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->created_at = $createdAt;

        return $this;
    }

    public function getUsedAt(): ?\DateTimeImmutable
    {
        return $this->used_at;
    }

    public function setUsedAt(?\DateTimeImmutable $usedAt): self
    {
        $this->used_at = $usedAt;

        return $this;
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $this->expires_at instanceof \DateTimeImmutable && $this->expires_at <= $now;
    }

    public function isUsed(): bool
    {
        return $this->used_at instanceof \DateTimeImmutable;
    }
}
