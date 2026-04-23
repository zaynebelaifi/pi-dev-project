<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use App\Repository\UserRepository;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $password_hash = null;

    public function getPassword(): ?string
    {
        return $this->password_hash;
    }

    public function setPassword(string $password): self
    {
        $this->password_hash = $password;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $role = null;

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        $role = strtoupper(trim((string) $this->role));
        if ($role === '') {
            $role = 'ROLE_CLIENT';
        }

        // ROLE_USER is always present to support generic protected routes.
        return array_values(array_unique([$role, 'ROLE_USER']));
    }

    public function setRoles(array $roles): self
    {
        $primaryRole = (string) ($roles[0] ?? 'ROLE_CLIENT');
        $this->role = strtoupper(trim($primaryRole));

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return strtolower(trim((string) ($this->email ?? '')));
    }

    public function eraseCredentials(): void
    {
        // No transient sensitive property is stored on the entity.
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $reference_id = null;

    public function getReference_id(): ?int
    {
        return $this->reference_id;
    }

    public function setReference_id(?int $reference_id): self
    {
        $this->reference_id = $reference_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $first_name = null;

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(?string $first_name): self
    {
        $this->first_name = $first_name;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $last_name = null;

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $last_name): self
    {
        $this->last_name = $last_name;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $banned = false;

    public function isBanned(): bool
    {
        return $this->banned;
    }

    public function setBanned(bool $banned): self
    {
        $this->banned = $banned;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $phone_number = null;

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPhone_number(): ?string
    {
        return $this->phone_number;
    }

    public function setPhone_number(?string $phone_number): self
    {
        $this->phone_number = $phone_number;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->getPhone_number();
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        return $this->setPhone_number($phoneNumber);
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $is_active = true;

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->is_active = $isActive;

        return $this;
    }

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $is_verified = false;

    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->is_verified = $isVerified;

        return $this;
    }

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $profile_image = null;

    public function getProfileImage(): ?string
    {
        return $this->profile_image;
    }

    public function setProfileImage(?string $profileImage): self
    {
        $this->profile_image = $profileImage;

        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->created_at = $createdAt;

        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    #[ORM\OneToMany(targetEntity: AuditLog::class, mappedBy: 'actor')]
    private Collection $audit_logs;

    /**
     * @return Collection<int, AuditLog>
     */
    public function getAuditLogs(): Collection
    {
        if (!$this->audit_logs instanceof Collection) {
            $this->audit_logs = new ArrayCollection();
        }

        return $this->audit_logs;
    }

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'recipient')]
    private Collection $notifications;

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        if (!$this->notifications instanceof Collection) {
            $this->notifications = new ArrayCollection();
        }

        return $this->notifications;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

}
