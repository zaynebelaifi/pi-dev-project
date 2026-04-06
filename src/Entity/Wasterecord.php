<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\WasterecordRepository;

#[ORM\Entity(repositoryClass: WasterecordRepository::class)]
#[ORM\Table(name: 'wasterecord')]
class Wasterecord
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

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $ingredientId = null;

    public function getIngredientId(): ?int
    {
        return $this->ingredientId;
    }

    public function setIngredientId(int $ingredientId): self
    {
        $this->ingredientId = $ingredientId;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $quantityWasted = null;

    public function getQuantityWasted(): ?float
    {
        return $this->quantityWasted;
    }

    public function setQuantityWasted(float $quantityWasted): self
    {
        $this->quantityWasted = $quantityWasted;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $wasteType = null;

    public function getWasteType(): ?string
    {
        return $this->wasteType;
    }

    public function setWasteType(string $wasteType): self
    {
        $this->wasteType = $wasteType;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date = null;

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $reason = null;

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

}
