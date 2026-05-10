<?php

namespace App\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Phone
{
    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'Phone number is required.')]
    #[Assert\Regex(pattern: '/^\d{8}$/', message: 'Phone number must be exactly 8 digits.')]
    private string $number;

    public function __construct(string $number)
    {
        $this->number = $number;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;
        return $this;
    }

    public function __toString(): string
    {
        return $this->number;
    }

    public function equals(Phone $other): bool
    {
        return $this->number === $other->number;
    }
}
