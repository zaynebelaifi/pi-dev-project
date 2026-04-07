<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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

    #[ORM\ManyToOne(targetEntity: Ingredient::class, inversedBy: 'wasteRecords')]
    #[ORM\JoinColumn(name: 'ingredientId', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'Ingredient is required.')]
    private ?Ingredient $ingredient = null;

    public function getIngredient(): ?Ingredient
    {
        return $this->ingredient;
    }

    public function setIngredient(?Ingredient $ingredient): self
    {
        $this->ingredient = $ingredient;
        return $this;
    }

    public function getIngredientId(): ?int
    {
        return $this->ingredient?->getId();
    }

    #[ORM\Column(name: 'quantityWasted', type: 'decimal', nullable: false)]
    #[Assert\NotNull(message: 'Wasted quantity is required.')]
    #[Assert\Positive(message: 'Wasted quantity must be greater than 0.')]
    private ?float $quantityWasted = null;

    public function getQuantityWasted(): ?float
    {
        return $this->quantityWasted;
    }

    public function setQuantityWasted(?float $quantityWasted): self
    {
        $this->quantityWasted = $quantityWasted;
        return $this;
    }

    #[ORM\Column(name: 'wasteType', type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Waste type is required.')]
    #[Assert\Length(max: 255, maxMessage: 'Waste type cannot exceed {{ limit }} characters.')]
    private ?string $wasteType = null;

    public function getWasteType(): ?string
    {
        return $this->wasteType;
    }

    public function setWasteType(?string $wasteType): self
    {
        $this->wasteType = $wasteType;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    #[Assert\NotNull(message: 'Waste date is required.')]
    private ?\DateTimeInterface $date = null;

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank(message: 'Reason is required.')]
    #[Assert\Length(max: 255, maxMessage: 'Reason cannot exceed {{ limit }} characters.')]
    private ?string $reason = null;

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

}
