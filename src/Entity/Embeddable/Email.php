<?php

namespace App\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Email
{
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    #[Assert\Length(max: 255, maxMessage: 'Email address cannot be longer than {{ limit }} characters.')]
    private ?string $address = null;

    public function __construct(?string $address = null)
    {
        $this->address = $address;
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

    public function __toString(): string
    {
        return $this->address ?? '';
    }

    public function equals(Email $other): bool
    {
        return $this->address === $other->address;
    }

    public function isEmpty(): bool
    {
        return $this->address === null;
    }
}
