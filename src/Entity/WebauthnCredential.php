<?php

namespace App\Entity;

use App\Repository\WebauthnCredentialDoctrineRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WebauthnCredentialDoctrineRepository::class)]
#[ORM\Table(name: 'webauthn_credential')]
#[ORM\UniqueConstraint(name: 'uniq_webauthn_credential_id', columns: ['credential_id'])]
#[ORM\Index(name: 'idx_webauthn_user_handle', columns: ['user_handle'])]
class WebauthnCredential
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 512)]
    private ?string $credential_id = null;

    #[ORM\Column(type: 'string', length: 128)]
    private ?string $user_handle = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: 'text')]
    private ?string $source_json = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $public_key = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $counter = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updated_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCredentialId(): ?string
    {
        return $this->credential_id;
    }

    public function setCredentialId(string $credentialId): self
    {
        $this->credential_id = $credentialId;

        return $this;
    }

    public function getUserHandle(): ?string
    {
        return $this->user_handle;
    }

    public function setUserHandle(string $userHandle): self
    {
        $this->user_handle = $userHandle;

        return $this;
    }

    public function getSourceJson(): ?string
    {
        return $this->source_json;
    }

    public function setSourceJson(string $sourceJson): self
    {
        $this->source_json = $sourceJson;

        return $this;
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

    public function getPublicKey(): ?string
    {
        return $this->public_key;
    }

    public function setPublicKey(?string $publicKey): self
    {
        $this->public_key = $publicKey;

        return $this;
    }

    public function getCounter(): int
    {
        return $this->counter;
    }

    public function setCounter(int $counter): self
    {
        $this->counter = max(0, $counter);

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updated_at = $updatedAt;

        return $this;
    }
}
