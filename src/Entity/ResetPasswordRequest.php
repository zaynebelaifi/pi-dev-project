<?php

namespace App\Entity;

use App\Repository\ResetPasswordRequestRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Stores a single-use, time-limited password reset token for a user.
 *
 * Security design:
 *  - The token stored in the database is a SHA-256 hash of the real token.
 *  - The real (plain) token is only ever sent in the email link and never persisted.
 *  - Tokens expire after 1 hour (configurable via RESET_TOKEN_TTL_SECONDS).
 *  - A user can only have one active reset request at a time (old ones are deleted on new request).
 */
#[ORM\Entity(repositoryClass: ResetPasswordRequestRepository::class)]
#[ORM\Table(name: 'reset_password_request')]
class ResetPasswordRequest
{
    /** Token lifetime in seconds (1 hour). */
    public const TOKEN_TTL = 3600;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * The user who requested the password reset.
     * We store the user ID directly to avoid a full ORM join on every token lookup.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * Selector prefix — present in the existing DB schema (legacy column, unused by this implementation).
     * Kept nullable so Doctrine does not complain about a missing value.
     */
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $selector = null;

    /**
     * SHA-256 hash of the real token (never store the plain token).
     */
    #[ORM\Column(type: 'string', length: 100)]
    private string $hashedToken;

    /**
     * When this token was created.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $requestedAt;

    /**
     * When this token expires.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    public function __construct(User $user, string $hashedToken)
    {
        $this->user        = $user;
        $this->hashedToken = $hashedToken;
        $this->requestedAt = new \DateTimeImmutable();
        $this->expiresAt   = new \DateTimeImmutable(sprintf('+%d seconds', self::TOKEN_TTL));
    }

    // ----------------------------------------------------------------
    // Getters
    // ----------------------------------------------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getHashedToken(): string
    {
        return $this->hashedToken;
    }

    public function getRequestedAt(): \DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Returns true if the token has passed its expiry time.
     */
    public function isExpired(): bool
    {
        return $this->expiresAt <= new \DateTimeImmutable();
    }
}
