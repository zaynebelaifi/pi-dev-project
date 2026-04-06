<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\IngredientRepository;

#[ORM\Entity(repositoryClass: IngredientRepository::class)]
#[ORM\Table(name: 'ingredient')]
class Ingredient
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
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $quantityInStock = null;

    public function getQuantityInStock(): ?float
    {
        return $this->quantityInStock;
    }

    public function setQuantityInStock(float $quantityInStock): self
    {
        $this->quantityInStock = $quantityInStock;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $unit = null;

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $minStockLevel = null;

    public function getMinStockLevel(): ?float
    {
        return $this->minStockLevel;
    }

    public function setMinStockLevel(float $minStockLevel): self
    {
        $this->minStockLevel = $minStockLevel;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $unitCost = null;

    public function getUnitCost(): ?float
    {
        return $this->unitCost;
    }

    public function setUnitCost(float $unitCost): self
    {
        $this->unitCost = $unitCost;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $expiryDate = null;

    public function getExpiryDate(): ?\DateTimeInterface
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(\DateTimeInterface $expiryDate): self
    {
        $this->expiryDate = $expiryDate;
        return $this;
    }

}
